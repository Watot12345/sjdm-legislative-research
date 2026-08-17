<?php
class FileAnalyzer {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function analyzeDocument($doc_id) {
        $sql = "SELECT * FROM policy_documents WHERE document_id = '$doc_id'";
        $result = $this->conn->query($sql);
        $document = $result->fetch_assoc();
        
        if (!$document) {
            return ['error' => 'Document not found', 'success' => false];
        }
        
        if (!file_exists($document['file_path'])) {
            return ['error' => 'File not found at: ' . $document['file_path'], 'success' => false];
        }
        
        // Extract text from file
        $text_content = $this->extractTextFromFile($document['file_path']);
        
        if (!$text_content || strlen(trim($text_content)) < 10) {
            // Use document metadata as fallback
            $text_content = $document['title'] . ' ' . $document['category'] . ' ' . $document['keywords'] . ' ' . $document['description'];
        }
        
        $analysis = $this->analyzeContent($text_content, $document);
        $this->saveAnalysisResults($doc_id, $analysis);
        
        return $analysis;
    }
    
    private function extractTextFromFile($file_path) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $content = @file_get_contents($file_path);
        
        if (!$content) return false;
        
        switch ($extension) {
            case 'txt':
                return $content;
                
            case 'pdf':
                // Basic PDF text extraction
                $text = '';
                preg_match_all('/\(([^)]*)\)/', $content, $matches);
                if (isset($matches[1])) {
                    foreach ($matches[1] as $match) {
                        if (strlen($match) > 2) {
                            $text .= $match . ' ';
                        }
                    }
                }
                return $this->cleanText($text);
                
            case 'docx':
                // Extract text from DOCX without ZipArchive
                $text = '';
                
                // Look for w:t tags (Word text)
                preg_match_all('/<w:t[^>]*>([^<]+)<\/w:t>/i', $content, $matches);
                if (isset($matches[1])) {
                    foreach ($matches[1] as $match) {
                        $clean = trim($match);
                        if (!empty($clean)) {
                            $text .= $clean . ' ';
                        }
                    }
                }
                
                // If no text found, try another pattern
                if (empty(trim($text))) {
                    preg_match_all('/>([^<]{3,})</', $content, $matches);
                    if (isset($matches[1])) {
                        foreach ($matches[1] as $match) {
                            $clean = trim($match);
                            if (!empty($clean) && strlen($clean) > 3) {
                                $text .= $clean . ' ';
                            }
                        }
                    }
                }
                
                return $this->cleanText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
                
            case 'doc':
                // Basic DOC extraction
                $text = preg_replace('/[^a-zA-Z0-9\s\.\,\;\:\-\"\']/', ' ', $content);
                return $this->cleanText($text);
                
            default:
                // Try to extract any readable text
                $text = preg_replace('/[^a-zA-Z0-9\s\.\,\;\:\-\"\']/', ' ', $content);
                return $this->cleanText($text);
        }
    }
    
    private function cleanText($text) {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        return $text;
    }
    
    private function analyzeContent($text, $document) {
        $keywords = $this->extractKeywords($text);
        
        return [
            'nlp_keywords' => implode(', ', array_slice($keywords, 0, 10)),
            'similar_ordinance' => $this->findSimilarOrdinances($text),
            'legal_citation' => $this->generateLegalCitations($text),
            'full_result' => $this->generateAnalysisResult($text, $document),
            'success' => true
        ];
    }
    
    private function extractKeywords($text) {
        $stopwords = ['the', 'a', 'an', 'and', 'or', 'but', 'for', 'of', 'with', 'without',
                      'ang', 'ng', 'sa', 'na', 'at', 'ay', 'ito', 'iyan', 'iyon', 'may', 'mga',
                      'isang', 'para', 'kay', 'kina', 'sina', 'ni', 'nina', 'to', 'from', 'by',
                      'on', 'in', 'at', 'for', 'with', 'without', 'through', 'during'];
        
        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, function($word) use ($stopwords) {
            return !in_array($word, $stopwords) && strlen($word) > 2;
        });
        
        $word_count = array_count_values($words);
        arsort($word_count);
        return array_keys(array_slice($word_count, 0, 20));
    }
    
    private function findSimilarOrdinances($text) {
        $text_lower = strtolower($text);
        $ordinances = [];
        
        $ordinance_types = [
            'Waste Management Ordinance' => ['waste', 'management', 'solid', 'garbage', 'disposal', 'recycle'],
            'Business Permit and Licensing Ordinance' => ['business', 'permit', 'license', 'commercial', 'trade'],
            'Public Health and Sanitation Ordinance' => ['health', 'sanitation', 'hygiene', 'cleanliness', 'medical'],
            'Environmental Protection Ordinance' => ['environment', 'protection', 'pollution', 'conservation'],
            'Local Tax Ordinance' => ['tax', 'revenue', 'assessment', 'collection', 'fee'],
            'Zoning Ordinance' => ['zoning', 'land', 'use', 'development', 'construction', 'building'],
            'Anti-Smoking Ordinance' => ['smoking', 'tobacco', 'cigarette', 'health'],
            'Animal Control Ordinance' => ['animal', 'pet', 'dog', 'cat', 'control'],
            'Noise Control Ordinance' => ['noise', 'sound', 'disturbance', 'quiet'],
            'Traffic Management Ordinance' => ['traffic', 'vehicle', 'road', 'parking', 'transportation'],
            'Disaster Risk Reduction Ordinance' => ['disaster', 'risk', 'reduction', 'emergency', 'rescue'],
            'Tourism Development Ordinance' => ['tourism', 'tourist', 'promotion', 'travel']
        ];
        
        foreach ($ordinance_types as $name => $keywords) {
            $match_count = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text_lower, $keyword) !== false) {
                    $match_count++;
                }
            }
            if ($match_count >= 2) {
                $ordinances[] = $name;
            }
        }
        
        if (empty($ordinances)) {
            $ordinances = [
                'Comprehensive Development Ordinance',
                'Public Service Delivery Ordinance',
                'Local Governance Ordinance'
            ];
        }
        
        return "Similar ordinances in San Jose del Monte, Bulacan:\n- " . implode("\n- ", array_slice($ordinances, 0, 5));
    }
    
    private function generateLegalCitations($text) {
        $text_lower = strtolower($text);
        $citations = [];
        
        $ph_laws = [
            'Republic Act No. 9003' => 'Ecological Solid Waste Management Act of 2000',
            'Republic Act No. 9275' => 'Philippine Clean Water Act of 2004',
            'Republic Act No. 8749' => 'Philippine Clean Air Act of 1999',
            'Republic Act No. 7160' => 'Local Government Code of 1991',
            'Republic Act No. 10175' => 'Cybercrime Prevention Act of 2012',
            'Republic Act No. 9710' => 'Magna Carta of Women',
            'Republic Act No. 9262' => 'Anti-Violence Against Women and Children Act of 2004',
            'Presidential Decree No. 1096' => 'National Building Code of the Philippines',
            'Presidential Decree No. 1586' => 'Environmental Impact Statement System',
            'Executive Order No. 292' => 'Administrative Code of 1987'
        ];
        
        foreach ($ph_laws as $law => $desc) {
            if (strpos($text_lower, strtolower($law)) !== false) {
                $citations[] = "$law - $desc";
            }
        }
        
        if (empty($citations)) {
            // Provide default citations based on content
            if (strpos($text_lower, 'waste') !== false || strpos($text_lower, 'environment') !== false) {
                $citations = [
                    'Republic Act No. 9003 - Ecological Solid Waste Management Act of 2000',
                    'Republic Act No. 9275 - Philippine Clean Water Act of 2004',
                    'Republic Act No. 8749 - Philippine Clean Air Act of 1999'
                ];
            } elseif (strpos($text_lower, 'business') !== false || strpos($text_lower, 'tax') !== false) {
                $citations = [
                    'Republic Act No. 7160 - Local Government Code of 1991',
                    'Republic Act No. 8424 - National Internal Revenue Code'
                ];
            } elseif (strpos($text_lower, 'health') !== false) {
                $citations = [
                    'Republic Act No. 7160 - Local Government Code of 1991',
                    'Republic Act No. 11223 - Universal Health Care Act'
                ];
            } else {
                $citations = [
                    'Republic Act No. 7160 - Local Government Code of 1991',
                    'Republic Act No. 9003 - Ecological Solid Waste Management Act'
                ];
            }
        }
        
        return "Relevant Legal Citations (Philippines):\n- " . implode("\n- ", array_slice($citations, 0, 8));
    }
    
    private function generateAnalysisResult($text, $document) {
        $result = "AI ANALYSIS REPORT\n";
        $result .= "====================\n\n";
        $result .= "Document: {$document['title']}\n";
        $result .= "Document ID: {$document['document_id']}\n";
        $result .= "Category: {$document['category']}\n";
        $result .= "Researcher: {$document['researcher']}\n";
        $result .= "Analysis Date: " . date('F j, Y g:i A') . "\n\n";
        
        // Document summary
        $sentences = preg_split('/[.!?]+/', $text);
        $summary = implode('. ', array_slice($sentences, 0, min(5, count($sentences))));
        if (strlen($summary) > 300) {
            $summary = substr($summary, 0, 300) . '...';
        }
        $result .= "DOCUMENT SUMMARY:\n";
        $result .= str_repeat('-', 40) . "\n";
        $result .= "$summary\n\n";
        
        // Key themes
        $keywords = $this->extractKeywords($text);
        $result .= "KEY THEMES & KEYWORDS:\n";
        $result .= str_repeat('-', 40) . "\n";
        $result .= "- " . implode("\n- ", array_slice($keywords, 0, 10)) . "\n\n";
        
        // Policy implications
        $policy_topics = [
            'waste' => 'Waste Management',
            'environment' => 'Environmental Protection',
            'health' => 'Public Health',
            'business' => 'Business and Commerce',
            'tax' => 'Taxation and Revenue',
            'zoning' => 'Zoning and Land Use',
            'safety' => 'Public Safety',
            'education' => 'Education',
            'infrastructure' => 'Infrastructure Development',
            'transportation' => 'Transportation'
        ];
        
        $identified_topics = [];
        $text_lower = strtolower($text);
        foreach ($policy_topics as $key => $label) {
            if (strpos($text_lower, $key) !== false) {
                $identified_topics[] = $label;
            }
        }
        
        if (!empty($identified_topics)) {
            $result .= "POLICY IMPLICATIONS:\n";
            $result .= str_repeat('-', 40) . "\n";
            $result .= "This document addresses the following policy areas:\n";
            $result .= "- " . implode("\n- ", array_unique($identified_topics)) . "\n\n";
        }
        
        // Recommendations
        $result .= "RECOMMENDATIONS:\n";
        $result .= str_repeat('-', 40) . "\n";
        $result .= "1. Review the document for alignment with existing local ordinances in San Jose del Monte.\n";
        $result .= "2. Consider updating the policy to incorporate best practices in local governance.\n";
        $result .= "3. Ensure compliance with relevant national laws and regulations.\n";
        $result .= "4. Conduct community consultation for policy implementation.\n";
        $result .= "5. Establish regular monitoring and evaluation mechanisms.\n";
        $result .= "6. Develop an implementation roadmap with clear milestones.\n\n";
        
        // Risk assessment
        $risk_level = $this->assessRisk($text);
        $result .= "RISK ASSESSMENT:\n";
        $result .= str_repeat('-', 40) . "\n";
        $result .= "Risk Level: $risk_level\n\n";
        
        // Document metrics
        $word_count = str_word_count($text);
        $result .= "DOCUMENT METRICS:\n";
        $result .= str_repeat('-', 40) . "\n";
        $result .= "- Total Words: " . number_format($word_count) . "\n";
        $result .= "- Unique Keywords: " . count($keywords) . "\n";
        $result .= "- Estimated Reading Time: " . ceil($word_count / 200) . " minutes\n";
        
        return $result;
    }
    
    private function assessRisk($text) {
        $text_lower = strtolower($text);
        $score = 0;
        
        $high_risk = ['emergency', 'critical', 'immediate', 'urgent', 'high risk', 'serious', 'danger', 'crisis'];
        $medium_risk = ['concern', 'issue', 'problem', 'challenge', 'medium risk', 'significant'];
        $low_risk = ['routine', 'standard', 'normal', 'regular', 'low risk', 'minor'];
        
        foreach ($high_risk as $word) {
            if (strpos($text_lower, $word) !== false) $score += 3;
        }
        foreach ($medium_risk as $word) {
            if (strpos($text_lower, $word) !== false) $score += 2;
        }
        foreach ($low_risk as $word) {
            if (strpos($text_lower, $word) !== false) $score += 1;
        }
        
        if ($score >= 6) return 'HIGH - Immediate attention required';
        if ($score >= 3) return 'MEDIUM - Requires monitoring and review';
        return 'LOW - Standard policy document';
    }
    
    private function saveAnalysisResults($doc_id, $analysis) {
        if ($analysis['success']) {
            $nlp_keywords = $this->conn->real_escape_string($analysis['nlp_keywords']);
            $similar_ordinance = $this->conn->real_escape_string($analysis['similar_ordinance']);
            $legal_citation = $this->conn->real_escape_string($analysis['legal_citation']);
            $full_result = $this->conn->real_escape_string($analysis['full_result']);
            
            $sql = "UPDATE policy_documents SET 
                    nlp_keywords = '$nlp_keywords',
                    similar_ordinance = '$similar_ordinance',
                    legal_citation = '$legal_citation',
                    ai_analysis_result = '$full_result',
                    ai_processed = 'Yes',
                    ai_processed_date = NOW()
                    WHERE document_id = '$doc_id'";
            
            $this->conn->query($sql);
        }
    }
}
?>
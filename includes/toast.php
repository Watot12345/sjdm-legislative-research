<?php
/**
 * Global Toast Notification Renderer
 * Legislative Research System - SJDM
 *
 * Supports 'success', 'error', 'warning', and 'info' flash notifications.
 */

if (!function_exists('renderToast')) {
    function renderToast($type = null, $title = null, $message = null) {
        if (!$type && isset($_SESSION['toast'])) {
            $toast = $_SESSION['toast'];
            $type = $toast['type'] ?? 'info';
            $title = $toast['title'] ?? '';
            $message = $toast['message'] ?? '';
            unset($_SESSION['toast']);
        }

        if (!$type || !$message) {
            return;
        }

        $configs = [
            'success' => [
                'icon' => 'fa-solid fa-circle-check',
                'bg_icon' => 'bg-emerald-50 text-emerald-600 border border-emerald-100',
                'bar_color' => 'bg-emerald-500',
                'title_color' => 'text-slate-800',
                'badge_color' => 'text-emerald-700 font-semibold',
                'default_title' => 'Success'
            ],
            'error' => [
                'icon' => 'fa-solid fa-circle-xmark',
                'bg_icon' => 'bg-rose-50 text-rose-600 border border-rose-100',
                'bar_color' => 'bg-rose-500',
                'title_color' => 'text-slate-800',
                'badge_color' => 'text-rose-700 font-semibold',
                'default_title' => 'Error'
            ],
            'warning' => [
                'icon' => 'fa-solid fa-triangle-exclamation',
                'bg_icon' => 'bg-amber-50 text-amber-600 border border-amber-100',
                'bar_color' => 'bg-amber-500',
                'title_color' => 'text-slate-800',
                'badge_color' => 'text-amber-700 font-semibold',
                'default_title' => 'Notice'
            ],
            'info' => [
                'icon' => 'fa-solid fa-circle-info',
                'bg_icon' => 'bg-blue-50 text-blue-600 border border-blue-100',
                'bar_color' => 'bg-blue-600',
                'title_color' => 'text-slate-800',
                'badge_color' => 'text-blue-700 font-semibold',
                'default_title' => 'Information'
            ]
        ];

        $cfg = $configs[$type] ?? $configs['info'];
        $displayTitle = $title ?: $cfg['default_title'];
        $toastId = 'toast_' . bin2hex(random_bytes(4));
        ?>
        <div id="<?php echo $toastId; ?>" 
             class="fixed top-5 right-5 z-[9999] max-w-sm w-full bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all duration-300 ease-out translate-y-[-20px] opacity-0 pointer-events-auto"
             role="alert">
            <div class="p-4 flex items-start gap-3.5">
                <div class="w-10 h-10 rounded-xl <?php echo $cfg['bg_icon']; ?> flex items-center justify-center shrink-0 shadow-sm text-lg">
                    <i class="<?php echo $cfg['icon']; ?>"></i>
                </div>
                <div class="flex-1 min-w-0 pt-0.5">
                    <div class="flex items-center justify-between gap-2">
                        <h4 class="text-sm font-bold <?php echo $cfg['title_color']; ?> leading-tight">
                            <?php echo htmlspecialchars($displayTitle); ?>
                        </h4>
                    </div>
                    <p class="text-xs text-slate-600 mt-1 leading-relaxed break-words">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>
                <button type="button" 
                        onclick="dismissToast('<?php echo $toastId; ?>')" 
                        class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition shrink-0" 
                        aria-label="Close">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
            <!-- Progress bar timer -->
            <div class="h-1 w-full bg-slate-100 overflow-hidden">
                <div id="<?php echo $toastId; ?>_bar" class="h-full <?php echo $cfg['bar_color']; ?> w-full transition-all duration-[4500ms] ease-linear"></div>
            </div>
        </div>
        <script>
        (function() {
            const toast = document.getElementById('<?php echo $toastId; ?>');
            const bar = document.getElementById('<?php echo $toastId; ?>_bar');
            if (!toast) return;

            // Animate in
            requestAnimationFrame(() => {
                setTimeout(() => {
                    toast.classList.remove('translate-y-[-20px]', 'opacity-0');
                    toast.classList.add('translate-y-0', 'opacity-100');
                    if (bar) {
                        bar.style.width = '0%';
                    }
                }, 50);
            });

            // Auto dismiss after 4.5s
            const autoDismissTimer = setTimeout(() => {
                dismissToast('<?php echo $toastId; ?>');
            }, 4500);

            window.dismissToast = window.dismissToast || function(id) {
                const el = document.getElementById(id);
                if (!el) return;
                el.classList.remove('translate-y-0', 'opacity-100');
                el.classList.add('translate-y-[-20px]', 'opacity-0');
                setTimeout(() => { el.remove(); }, 350);
            };
        })();
        </script>
        <?php
    }
}

import { ref, onMounted, onUnmounted, type Ref } from 'vue';

/**
 * Composable for scroll-based reveal animations using IntersectionObserver.
 * Elements with [data-animate] will be animated when they enter the viewport.
 * 
 * Usage in a component:
 *   const { containerRef } = useRevealAnimation();
 *   <div ref="containerRef"> ... <div data-animate="fade-up" data-delay="100"> ... </div> </div>
 * 
 * Supported data-animate values:
 *   fade-up, fade-down, fade-left, fade-right, scale-up, flip-up, blur-in, slide-in
 * 
 * Optional attributes:
 *   data-delay="200"        → delay in ms before animation triggers
 *   data-duration="600"     → animation duration in ms (default 600ms)
 *   data-stagger="true"     → auto-stagger children within a container
 */

export function useRevealAnimation(options?: {
    threshold?: number;
    rootMargin?: string;
}) {
    const containerRef: Ref<HTMLElement | null> = ref(null);
    let observer: IntersectionObserver | null = null;

    const threshold = options?.threshold ?? 0.08;
    const rootMargin = options?.rootMargin ?? '0px 0px -40px 0px';

    onMounted(() => {
        if (!containerRef.value) return;

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const el = entry.target as HTMLElement;
                        const delay = parseInt(el.dataset.delay || '0', 10);

                        setTimeout(() => {
                            el.classList.add('anim-visible');
                            el.classList.remove('anim-hidden');
                        }, delay);

                        observer?.unobserve(el);
                    }
                });
            },
            { threshold, rootMargin }
        );

        // Find all elements with data-animate
        const animatedElements = containerRef.value.querySelectorAll('[data-animate]');
        animatedElements.forEach((el, index) => {
            const htmlEl = el as HTMLElement;
            
            // Auto-stagger: if parent has data-stagger, apply incremental delays
            if (!htmlEl.dataset.delay && htmlEl.closest('[data-stagger]')) {
                const siblings = Array.from(htmlEl.closest('[data-stagger]')!.querySelectorAll(':scope > [data-animate]'));
                const sibIndex = siblings.indexOf(htmlEl);
                if (sibIndex >= 0) {
                    htmlEl.dataset.delay = String(sibIndex * 80);
                }
            }

            // Set initial hidden state
            htmlEl.classList.add('anim-hidden');
            observer?.observe(htmlEl);
        });
    });

    onUnmounted(() => {
        observer?.disconnect();
    });

    return { containerRef };
}

/**
 * Composable for page-load entrance animation.
 * Triggers a sequence of animations when the component mounts.
 */
export function usePageEntrance() {
    const isLoaded = ref(false);

    onMounted(() => {
        // Small delay to let the DOM paint first
        requestAnimationFrame(() => {
            setTimeout(() => {
                isLoaded.value = true;
            }, 50);
        });
    });

    return { isLoaded };
}

/**
 * Number counter animation composable.
 * Animates a number from 0 to the target value.
 */
export function useCountUp(targetValue: Ref<number>, duration: number = 1200) {
    const displayValue = ref(0);
    let animationFrame: number | null = null;

    const animate = () => {
        const start = performance.now();
        const startVal = 0;
        const endVal = targetValue.value;

        const step = (timestamp: number) => {
            const elapsed = timestamp - start;
            const progress = Math.min(elapsed / duration, 1);
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            displayValue.value = Math.round(startVal + (endVal - startVal) * eased);

            if (progress < 1) {
                animationFrame = requestAnimationFrame(step);
            }
        };

        animationFrame = requestAnimationFrame(step);
    };

    onMounted(() => {
        setTimeout(animate, 300);
    });

    onUnmounted(() => {
        if (animationFrame) cancelAnimationFrame(animationFrame);
    });

    return { displayValue };
}

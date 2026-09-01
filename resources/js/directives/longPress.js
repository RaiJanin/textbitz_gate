export const vLongPress = {
  mounted(el, binding) {
    const duration = binding.arg ? parseInt(binding.arg) : 600; // default 600ms
    let timer = null;
    let didFire = false;

    const start = (e) => {
      // ignore right-click
      if (e.type === 'click' && e.button !== 0) return;

      didFire = false;
      timer = setTimeout(() => {
        didFire = true;
        binding.value(e); // your callback
      }, duration);
    };

    const cancel = () => {
      clearTimeout(timer);
    };

    // prevent the normal click from also firing after a long press
    const onClick = (e) => {
      if (didFire) {
        e.preventDefault();
        e.stopPropagation();
      }
    };

    el._longPressHandlers = { start, cancel, onClick };

    el.addEventListener('mousedown', start);
    el.addEventListener('touchstart', start, { passive: true });

    el.addEventListener('mouseup', cancel);
    el.addEventListener('mouseleave', cancel);
    el.addEventListener('touchend', cancel);
    el.addEventListener('touchmove', cancel);

    el.addEventListener('click', onClick);
  },
  unmounted(el) {
    const h = el._longPressHandlers;
    if (!h) return;
    el.removeEventListener('mousedown', h.start);
    el.removeEventListener('touchstart', h.start);
    el.removeEventListener('mouseup', h.cancel);
    el.removeEventListener('mouseleave', h.cancel);
    el.removeEventListener('touchend', h.cancel);
    el.removeEventListener('touchmove', h.cancel);
    el.removeEventListener('click', h.onClick);
  },
};
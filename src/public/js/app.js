const deleteForms = document.querySelectorAll("[data-confirm-delete]");
deleteForms.forEach((form) => {
  form.addEventListener("submit", (event) => {
    const message = form.getAttribute("data-confirm-delete") || "Yakin ingin menghapus data ini?";
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });
});

let revealObserver = null;

function initRevealAnimations(root = document) {
  const elements = Array.from(root.querySelectorAll("[data-reveal]"));
  if (elements.length === 0) {
    return;
  }

  if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
    elements.forEach((element) => {
      element.classList.add("is-visible");
    });
    return;
  }

  if (!revealObserver) {
    revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            revealObserver.unobserve(entry.target);
          }
        });
      },
      {
        root: null,
        threshold: 0.12,
        rootMargin: "0px 0px -8% 0px"
      }
    );
  }

  elements.forEach((element, index) => {
    if (element.dataset.revealInit === "1") {
      return;
    }

    element.dataset.revealInit = "1";
    const parsedDelay = Number.parseInt(element.dataset.revealDelay || "", 10);
    if (Number.isInteger(parsedDelay) && parsedDelay >= 0) {
      element.style.setProperty("--reveal-delay", `${parsedDelay}ms`);
    } else if (!element.style.getPropertyValue("--reveal-delay")) {
      element.style.setProperty("--reveal-delay", `${(index % 6) * 45}ms`);
    }
    revealObserver.observe(element);
  });
}

function bindRipple(target) {
  if (!target || target.dataset.rippleBound === "1") {
    return;
  }

  target.dataset.rippleBound = "1";
  target.classList.add("fx-ripple");

  target.addEventListener("pointerdown", (event) => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      return;
    }

    const rect = target.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height) * 0.7;
    const dot = document.createElement("span");
    dot.className = "fx-ripple-dot";
    dot.style.width = `${size}px`;
    dot.style.height = `${size}px`;
    dot.style.left = `${event.clientX - rect.left}px`;
    dot.style.top = `${event.clientY - rect.top}px`;
    target.appendChild(dot);
    window.setTimeout(() => dot.remove(), 650);
  });
}

function initMicroInteractions(root = document) {
  const rippleTargets = root.querySelectorAll(
    ".action-btn, .page-btn, .chip, .nav-item, [data-ripple]"
  );
  rippleTargets.forEach(bindRipple);
}

function initCardTilt(root = document) {
  const canTilt = window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  if (!canTilt) {
    return;
  }

  const cards = root.querySelectorAll(".product-card");
  cards.forEach((card) => {
    if (card.dataset.tiltBound === "1") {
      return;
    }

    card.dataset.tiltBound = "1";
    card.addEventListener("pointermove", (event) => {
      const rect = card.getBoundingClientRect();
      const px = (event.clientX - rect.left) / rect.width;
      const py = (event.clientY - rect.top) / rect.height;
      const tiltY = (px - 0.5) * 5.5;
      const tiltX = (0.5 - py) * 4.8;
      card.style.setProperty("--pc-tilt-x", `${tiltX.toFixed(2)}deg`);
      card.style.setProperty("--pc-tilt-y", `${tiltY.toFixed(2)}deg`);
    });

    card.addEventListener("pointerleave", () => {
      card.style.setProperty("--pc-tilt-x", "0deg");
      card.style.setProperty("--pc-tilt-y", "0deg");
    });
  });
}

function showToast(message) {
  const toast = document.createElement("div");
  toast.textContent = message;
  toast.className =
    "fixed left-1/2 top-5 z-[60] -translate-x-1/2 rounded-lg border border-emerald-400/40 bg-slate-900/95 px-3 py-2 text-xs font-semibold text-emerald-200 shadow-card";
  document.body.appendChild(toast);

  window.setTimeout(() => {
    toast.remove();
  }, 2000);
}

async function copyText(value) {
  if (!value) {
    return false;
  }

  try {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(value);
      return true;
    }
  } catch (error) {
    // noop
  }

  const input = document.createElement("input");
  input.value = value;
  document.body.appendChild(input);
  input.select();
  const copied = document.execCommand("copy");
  input.remove();
  return copied;
}

const copyButtons = document.querySelectorAll("[data-copy-link]");
copyButtons.forEach((button) => {
  button.addEventListener("click", async () => {
    const url = button.getAttribute("data-copy-url") || window.location.href;
    const copied = await copyText(url);
    showToast(copied ? "Link berhasil disalin" : "Gagal salin link");
  });
});

const shareButtons = document.querySelectorAll("[data-share-product]");
shareButtons.forEach((button) => {
  button.addEventListener("click", async () => {
    const url = button.getAttribute("data-share-url") || window.location.href;
    const title = document.title;

    if (navigator.share) {
      try {
        await navigator.share({ title, url });
        return;
      } catch (error) {
        // noop
      }
    }

    const copied = await copyText(url);
    showToast(copied ? "Link produk disalin" : "Share tidak tersedia");
  });
});

function initCatalogLiveSearch() {
  const form = document.querySelector("[data-live-search-form]");
  const input = form?.querySelector("[data-live-search]");
  if (!form || !input) {
    return;
  }

  let debounceTimer = null;
  let activeController = null;
  let isComposing = false;
  let lastSearchKey = null;

  const getDynamicNode = () => document.querySelector("[data-catalog-dynamic]");

  const setLoading = (loading) => {
    const dynamicNode = getDynamicNode();
    if (!dynamicNode) {
      return;
    }

    dynamicNode.classList.toggle("opacity-60", loading);
    dynamicNode.classList.toggle("pointer-events-none", loading);
  };

  const buildSearchUrl = () => {
    const action = form.getAttribute("action") || window.location.pathname;
    const url = new URL(action, window.location.origin);
    const params = new URLSearchParams(window.location.search);

    const q = input.value.trim();
    if (q) {
      params.set("q", q);
    } else {
      params.delete("q");
    }

    params.delete("page");

    const hiddenFields = form.querySelectorAll("input[type='hidden'][name]");
    hiddenFields.forEach((field) => {
      const value = String(field.value || "").trim();
      if (value) {
        params.set(field.name, value);
      } else {
        params.delete(field.name);
      }
    });

    url.search = params.toString();
    return url;
  };

  const performSearch = async () => {
    const url = buildSearchUrl();
    const searchKey = url.search;
    if (searchKey === lastSearchKey) {
      return;
    }
    lastSearchKey = searchKey;

    if (activeController) {
      activeController.abort();
    }
    activeController = new AbortController();

    setLoading(true);
    try {
      const response = await fetch(url.toString(), {
        signal: activeController.signal,
        headers: {
          "X-Requested-With": "live-search"
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const html = await response.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const nextDynamic = doc.querySelector("[data-catalog-dynamic]");
      const currentDynamic = getDynamicNode();

      if (!nextDynamic || !currentDynamic) {
        window.location.assign(url.toString());
        return;
      }

      currentDynamic.replaceWith(nextDynamic);
      window.history.replaceState({}, "", `${url.pathname}${url.search}`);
      initRevealAnimations(document);
      initMicroInteractions(document);
      initCardTilt(document);
    } catch (error) {
      if (error.name !== "AbortError") {
        form.submit();
      }
    } finally {
      setLoading(false);
    }
  };

  const scheduleSearch = (delay = 260) => {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(performSearch, delay);
  };

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    scheduleSearch(0);
  });

  input.addEventListener("compositionstart", () => {
    isComposing = true;
  });

  input.addEventListener("compositionend", () => {
    isComposing = false;
    scheduleSearch(180);
  });

  input.addEventListener("input", () => {
    if (isComposing) {
      return;
    }
    scheduleSearch(260);
  });
}

initCatalogLiveSearch();

function initProductCarousel() {
  const root = document.querySelector("[data-product-carousel-root]");
  if (!root) {
    return;
  }

  const viewport = root.querySelector("[data-product-carousel]");
  const track = root.querySelector("[data-carousel-track]");
  const slides = Array.from(root.querySelectorAll("[data-carousel-slide]"));
  const prevButton = root.querySelector("[data-carousel-prev]");
  const nextButton = root.querySelector("[data-carousel-next]");
  const thumbs = Array.from(root.querySelectorAll("[data-carousel-thumb]"));
  const counter = root.querySelector("[data-carousel-counter]");

  if (!viewport || !track || slides.length <= 1) {
    return;
  }

  let currentIndex = 0;
  const total = slides.length;

  const update = () => {
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    if (counter) {
      counter.textContent = `${currentIndex + 1} / ${total}`;
    }
    thumbs.forEach((thumb) => {
      const thumbIndex = Number.parseInt(thumb.dataset.index || "-1", 10);
      thumb.classList.toggle("is-active", thumbIndex === currentIndex);
    });
  };

  const goTo = (index) => {
    if (index < 0) {
      currentIndex = total - 1;
    } else if (index >= total) {
      currentIndex = 0;
    } else {
      currentIndex = index;
    }
    update();
  };

  prevButton?.addEventListener("click", () => goTo(currentIndex - 1));
  nextButton?.addEventListener("click", () => goTo(currentIndex + 1));

  thumbs.forEach((thumb) => {
    thumb.addEventListener("click", () => {
      const index = Number.parseInt(thumb.dataset.index || "0", 10);
      if (Number.isInteger(index)) {
        goTo(index);
      }
    });
  });

  let startX = 0;
  let deltaX = 0;
  let touching = false;

  viewport.addEventListener(
    "touchstart",
    (event) => {
      if (event.touches.length !== 1) {
        return;
      }
      touching = true;
      startX = event.touches[0].clientX;
      deltaX = 0;
    },
    { passive: true }
  );

  viewport.addEventListener(
    "touchmove",
    (event) => {
      if (!touching || event.touches.length !== 1) {
        return;
      }
      deltaX = event.touches[0].clientX - startX;
    },
    { passive: true }
  );

  viewport.addEventListener(
    "touchend",
    () => {
      if (!touching) {
        return;
      }
      touching = false;
      if (Math.abs(deltaX) > 45) {
        goTo(deltaX < 0 ? currentIndex + 1 : currentIndex - 1);
      }
    },
    { passive: true }
  );

  window.addEventListener("keydown", (event) => {
    if (document.body.classList.contains("has-lightbox-open")) {
      return;
    }

    const target = event.target;
    const tagName = target?.tagName || "";
    if (target?.isContentEditable || tagName === "INPUT" || tagName === "TEXTAREA" || tagName === "SELECT") {
      return;
    }

    if (event.key === "ArrowLeft") {
      goTo(currentIndex - 1);
    } else if (event.key === "ArrowRight") {
      goTo(currentIndex + 1);
    }
  });

  update();
}

function initProductLightbox() {
  const root = document.querySelector("[data-product-carousel-root]");
  const lightbox = document.querySelector("[data-product-lightbox]");
  if (!root || !lightbox) {
    return;
  }

  if (lightbox.dataset.bound === "1") {
    return;
  }
  lightbox.dataset.bound = "1";

  const lightboxImage = lightbox.querySelector("[data-lightbox-image]");
  const lightboxCounter = lightbox.querySelector("[data-lightbox-counter]");
  const closeButtons = Array.from(lightbox.querySelectorAll("[data-lightbox-close]"));
  const prevButton = lightbox.querySelector("[data-lightbox-prev]");
  const nextButton = lightbox.querySelector("[data-lightbox-next]");
  const images = Array.from(root.querySelectorAll("[data-carousel-slide] img"))
    .map((img) => ({
      src: img.getAttribute("src") || "",
      alt: img.getAttribute("alt") || "Preview produk"
    }))
    .filter((item) => item.src);

  if (!lightboxImage || images.length === 0) {
    return;
  }

  let currentIndex = 0;

  const normalizeIndex = (index) => {
    if (index < 0) {
      return images.length - 1;
    }
    if (index >= images.length) {
      return 0;
    }
    return index;
  };

  const renderImage = () => {
    const currentImage = images[currentIndex];
    lightboxImage.src = currentImage.src;
    lightboxImage.alt = currentImage.alt;
    if (lightboxCounter) {
      lightboxCounter.textContent = `${currentIndex + 1} / ${images.length}`;
    }
  };

  const openAt = (index) => {
    currentIndex = normalizeIndex(index);
    renderImage();
    lightbox.classList.add("is-open");
    lightbox.setAttribute("aria-hidden", "false");
    document.body.classList.add("has-lightbox-open");
  };

  const close = () => {
    lightbox.classList.remove("is-open");
    lightbox.setAttribute("aria-hidden", "true");
    document.body.classList.remove("has-lightbox-open");
  };

  const move = (delta) => {
    currentIndex = normalizeIndex(currentIndex + delta);
    renderImage();
  };

  const resolveTriggerIndex = (element) => {
    const index = Number.parseInt(element?.dataset?.index || "-1", 10);
    return Number.isInteger(index) && index >= 0 ? index : 0;
  };

  root.addEventListener("click", (event) => {
    const trigger = event.target.closest("[data-lightbox-open]");
    if (!trigger || !root.contains(trigger)) {
      return;
    }

    event.preventDefault();
    openAt(resolveTriggerIndex(trigger));
  });

  root.addEventListener("keydown", (event) => {
    const trigger = event.target.closest("[data-lightbox-open]");
    if (!trigger || !root.contains(trigger)) {
      return;
    }

    if (event.key !== "Enter" && event.key !== " ") {
      return;
    }

    event.preventDefault();
    openAt(resolveTriggerIndex(trigger));
  });

  closeButtons.forEach((button) => {
    button.addEventListener("click", close);
  });

  prevButton?.addEventListener("click", () => move(-1));
  nextButton?.addEventListener("click", () => move(1));

  window.addEventListener("keydown", (event) => {
    if (!lightbox.classList.contains("is-open")) {
      return;
    }

    if (event.key === "Escape") {
      close();
    } else if (event.key === "ArrowLeft") {
      move(-1);
    } else if (event.key === "ArrowRight") {
      move(1);
    }
  });
}

initProductCarousel();
initProductLightbox();
initRevealAnimations(document);
initMicroInteractions(document);
initCardTilt(document);

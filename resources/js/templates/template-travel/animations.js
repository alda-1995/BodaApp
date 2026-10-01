// Carrusel de las postales del bloque de historia (puede no existir en la vista).
const sliderModalTravels = typeof Swiper !== 'undefined' && document.querySelector('.sliderModalTravels')
  ? new Swiper('.sliderModalTravels', {
      slidesPerView: 1,
      effect: 'slide',
      spaceBetween: 16,
      navigation: {
        nextEl: '.next-int-travel',
        prevEl: '.prev-int-travel',
      },
    })
  : null;

/**
 * Anima el texto de un selector de clase línea por línea.
 * Dentro de cada línea, las letras aparecen ESCALONADAMENTE (stagger)
 * con opacidad de izquierda a derecha.
 *
 * @param {string} selector - El selector CSS (ej: '.mi-texto-animado').
 * @param {number} [duration=0.5] - Duración de la animación de CADA letra. (REDUCIDO)
 * @param {number} [charStagger=0.06] - Retraso entre CADA letra (el delay de izquierda a derecha). (REDUCIDO)
 * @param {number} [lineStagger=0.08] - Retraso entre la animación de CADA línea. (REDUCIDO)
 */
function animateTxtLeftToRigthOpacity(selector, duration = 0.3, charStagger = 0.06, lineStagger = 0.08) {
  // 1. Verificación de librerías.
  if (typeof gsap === 'undefined' || typeof SplitType === 'undefined') {
    console.error("GSAP o SplitType no están cargados. Asegúrate de incluirlos.");
    return;
  }

  const elements = document.querySelectorAll(selector);

  elements.forEach(element => {
    // 2. Dividir el texto por 'lines' Y 'chars' (letras)
    const splitText = new SplitType(element, {
      types: 'lines, chars',
      lineClass: 'line-animada'
    });

    // 3. Configurar el estado INICIAL de las letras: Opacidad 0
    gsap.set(splitText.chars, {
      opacity: 0,
    });

    // 4. Crear la Timeline con ScrollTrigger
    const tl = gsap.timeline({
      defaults: {
        ease: 'power2.out'
      },
      scrollTrigger: {
        trigger: element,
        start: 'top 80%',
        end: "top 80%",
        toggleActions: 'play none none none',
        markers: false
      }
    });

    // 5. Iterar sobre CADA LÍNEA para animar sus letras
    splitText.lines.forEach((line, index) => {
      const lineChars = line.querySelectorAll('.char');

      // 6. Aplicar la animación TO a las LETRAS DENTRO DE LA LÍNEA
      tl.to(lineChars, {
        opacity: 1, // Hace visible la letra
        duration: duration,
        // **CLAVE:** Usamos stagger para el delay entre letras (izquierda a derecha)
        stagger: charStagger,
      },
        // El tiempo de inicio de CADA LÍNEA (el escalonamiento línea por línea)
        index * lineStagger
      );
    });
  });
}

const animationFlowers = () => {
  const SECTION = document.getElementById('gift-section');
  const FLOWERS = document.querySelectorAll('.falling-flower-static');

  // Salir si faltan elementos
  if (!SECTION || FLOWERS.length === 0) return;

  // Calcular la altura del contenedor (SECTION) para usarlo como punto de inicio negativo (desde arriba).
  const SECTION_HEIGHT = SECTION.clientHeight;

  // 2. Configuración inicial de las flores estáticas
  FLOWERS.forEach(flower => {
    const targetFactor = parseFloat(flower.getAttribute('data-target-y')) || 1.0;
    const bottomPercentage = (1.0 - targetFactor) * 100;

    // Establecer posición absoluta para control manual
    flower.style.position = 'absolute';
    flower.style.bottom = `${bottomPercentage}%`;
  });

  /**
    * Crea y devuelve la línea de tiempo de animación para una sola flor.
    * @param {HTMLElement} flower El elemento DOM de la flor.
    */
  function createFlowerAnimation(flower) {

    const duration = 4; // Duración fija de la caída
    const delay = gsap.utils.random(0, 5); // Retraso aleatorio para escalonamiento
    const initialRotation = gsap.utils.random(360, 720);

    // 3a. Configuración inicial (Set)
    gsap.set(flower, {
      // Se usa el negativo de la altura de la sección para empezar desde ARRIBA del contenedor.
      y: -(SECTION_HEIGHT - flower.offsetHeight),
      rotation: initialRotation,
      opacity: 0,
    });

    // 3b. Crear Línea de Tiempo
    const tl = gsap.timeline({
      defaults: { ease: "linear" },
    });

    // Animación de opacidad a 1 al inicio
    tl.to(flower, {
      opacity: 1,
      duration: 0.5,
    }, delay);

    // Paso Único: Caída a la posición final (y: 0) y Rotación.
    tl.to(flower, {
      y: 0,
      rotation: 0,
      duration: duration,
    }, delay);

    return tl;
  }

  // 4. Crear línea de tiempo maestra
  const masterTimeline = gsap.timeline({ paused: true });

  FLOWERS.forEach(flower => {
    const flowerAnimation = createFlowerAnimation(flower);
    // Agrega todas las animaciones al mismo tiempo (posición 0)
    masterTimeline.add(flowerAnimation, 0);
  });

  // 5. ScrollTrigger para reproducir la animación al entrar a la vista
  ScrollTrigger.create({
    trigger: SECTION,
    start: "top bottom",
    end: "bottom top",
    markers: false,
    // Reproduce la animación una vez cuando la sección entra o re-entra.
    onEnter: () => masterTimeline.play(),
    onEnterBack: () => masterTimeline.play(),
  });
}

const galleryInit = () => {
  const galleryElement = document.getElementById('my-gallery');
  if (!galleryElement) return;

  const links = galleryElement.querySelectorAll('a');
  const promises = [];

  links.forEach(link => {
    const imgSrc = link.getAttribute('href');

    if (link.getAttribute('data-pswp-width') && link.getAttribute('data-pswp-height')) {
      return;
    }

    const imgPromise = new Promise((resolve) => {
      const img = new Image();

      img.onload = () => {
        link.setAttribute('data-pswp-width', img.naturalWidth);
        link.setAttribute('data-pswp-height', img.naturalHeight);
        resolve();
      };

      img.onerror = () => {
        console.error('Error al cargar la imagen para PhotoSwipe:', imgSrc);
        link.setAttribute('data-pswp-width', 1000);
        link.setAttribute('data-pswp-height', 800);
        resolve();
      };
      img.src = imgSrc;
    });

    promises.push(imgPromise);
  });

  Promise.all(promises)
    .then(() => {
      const lightbox = new PhotoSwipeLightbox({
        gallery: '#my-gallery',
        children: 'a',
        pswpModule: () => import('photoswipe')
      });

      lightbox.init();

    })
    .catch(error => {
      console.error('Error durante la inicialización de PhotoSwipe:', error);
    });
}

const animationPostCDMX = () => {
  // Sólo aplica al bloque de postales; si la plantilla no lo trae, no hay nada que animar.
  if (!document.querySelector('.bolet1')) {
    return;
  }

  const durationBase = 1.5;
  const rotationAmount = 15;

  const tl = gsap.timeline({
    repeat: -1,
    yoyo: true,
    ease: "back.inOut"
  });

  tl.to(".bolet1, .bolet2", {
    rotation: (i) => i % 2 === 0 ? rotationAmount : -rotationAmount,
    scale: 1.05,
    duration: durationBase,
    stagger: {
      each: 0.2,
      from: "start"
    }
  }, 0);

  gsap.to(".envolve", {
    rotation: 20,
    duration: durationBase,
    ease: "back.inOut",
    repeat: -1,
    yoyo: true
  });

  gsap.to(".bolet-green", {
    rotation: -20,
    duration: durationBase,
    ease: "back.inOut",
    repeat: -1,
    yoyo: true,
    delay: 2
  });

  gsap.to(".postal-scale", {
    scale: 1.1,
    y: 8,
    duration: durationBase,
    ease: "back.inOut",
    repeat: -1,
    yoyo: true,
    delay: 2.2
  });
};

const modalPosts = () => {
  const modal = document.querySelector('.modal-posts-slides');
  const cardTravels = document.querySelectorAll('.card-travel');
  const closeModalButtons = document.querySelectorAll('.close-modal-content');
  const indicadorMobile = document.getElementById('indicadorMobileSlide');

  // Sin modal en la plantilla no hay nada que abrir.
  if (!modal || !indicadorMobile) {
    return;
  }

  const openModal = () => {
    const tlMenuOpen = gsap.timeline();
    tlMenuOpen.set("html", { "overflow-y": "hidden" });
    tlMenuOpen.set('#modal-slides-travel', { zIndex: 9999 });
    tlMenuOpen.to(modal, { y: "0%", duration: 0.6, ease: 'power3.out' });
  };

  const closeModal = () => {
    const tlMenu = gsap.timeline();
    tlMenu.to(modal, { y: "100%", duration: 0.6, ease: 'power3.out' });
    tlMenu.set("html", { "overflow-y": "unset" });
    tlMenu.set('#modal-slides-travel', { zIndex: -1 });
  };

  closeModalButtons.forEach(button => {
    button.addEventListener('click', closeModal);
  });

  cardTravels.forEach((card, index) => {
    card.addEventListener('click', () => {
      let slideNumber = card.getAttribute("data-slide");
      sliderModalTravels?.slideTo(index, slideNumber);
      openModal();
      indicadorMobile.style.display = 'block';
      indicadorMobile.style.opacity = 0;
      gsap.to(indicadorMobile, { opacity: 1, duration: 0.5 });
      gsap.to(indicadorMobile, {
        opacity: 0,
        delay: 3, // se oculta después de 3 segundos
        duration: 0.5,
        onComplete: () => {
          indicadorMobile.style.display = 'none';
        }
      });
    });
  });
};

modalPosts();
/**
 * Inicializa un efecto Parallax robusto en un elemento usando GSAP y ScrollTrigger.
 *
 * @param {string} selector - Selector CSS para el elemento (e.g., '#hero-img').
 * @param {number} startMovement - Define la posición Y inicial de la imagen (e.g., -100).
 * @param {number} endMovement - Define la posición Y final de la imagen (e.g., 100).
 * @param {string} triggerSelector - Selector del elemento que 'dispara' la animación (opcional, por defecto es el propio selector).
 */
function applyParallax(selector, startMovement = -200, endMovement = 200, triggerSelector = selector) {
  // Verificar si GSAP y ScrollTrigger están disponibles
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
    console.error('GSAP o ScrollTrigger no están cargados. Asegúrate de incluir los scripts.');
    return;
  }

  gsap.fromTo(
    selector,
    { y: startMovement },
    {
      y: endMovement,
      ease: 'none',
      scrollTrigger: {
        trigger: triggerSelector,
        scrub: true,
        start: 'top bottom',
        end: 'bottom top',
        // markers: false,
      }
    }
  );
}

function animateBoatLeftWithScroll(selector, distance = 100, easeType = 'power1.inOut') {
  // Asegurarse de que GSAP y ScrollTrigger están cargados
  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
    console.error('GSAP o ScrollTrigger no están cargados. Incluye los scripts necesarios.');
    return;
  }

  gsap.to(
    selector,
    {
      x: distance,
      ease: easeType,
      scrollTrigger: {
        trigger: selector,
        scrub: true,
        start: 'top bottom',
        end: 'bottom center',
        // markers: false
      }
    }
  );
}

/** Botón de "volver arriba" del pie. */
const initScrollTop = () => {
  document.getElementById('btn-scroll-top')
    ?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
};

/** Animaciones que no dependen de los canvas. */
const startSupportAnimations = () => {
  const flowerParallax = document.querySelector('.img-parallax-flower');

  animateTxtLeftToRigthOpacity('.text-animated-opacity-split');
  // Los canvas fijan (pin) secciones: hay que recalcular posiciones después.
  ScrollTrigger.refresh();
  animationFlowers();

  if (flowerParallax) {
    applyParallax(flowerParallax, 1.5);
  }

  animateBoatLeftWithScroll('.boat-animation-move');
};

/**
 * Arranca todo el movimiento de la plantilla.
 *
 * Antes este archivo se ejecutaba solo y llamaba funciones globales de otros
 * archivos; ahora lo orquesta index.js, que le pasa las promesas de los canvas.
 *
 * @param {{frameAnimationPromise?: Promise, timeAnimationPromise?: Promise}} canvases
 */
export function initTemplateAnimations(canvases = {}) {
  galleryInit();
  initScrollTop();
  animationPostCDMX();

  const { frameAnimationPromise, timeAnimationPromise } = canvases;

  Promise.all([frameAnimationPromise, timeAnimationPromise])
    .then(startSupportAnimations)
    .catch((error) => {
      console.error('Falló la carga de algún canvas; el resto de animaciones continúa.', error);
      startSupportAnimations();
    });
}
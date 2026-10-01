/**
 * Inicializa una animación de secuencia de frames en un canvas controlada por scroll,
 * seguida de la animación del contenido de la tarjeta, todo en una única línea de tiempo de GSAP.
 * @param {string} canvasSelector - Selector del elemento <canvas>.
 * @param {string} triggerSelector - Selector del elemento que actúa como punto de inicio y pin.
 * @param {string} contentSelector - Selector del contenedor del contenido de la card a animar.
 * @param {number} frameCount - El número total de frames (ej: 7).
 * @param {string} basePath - La ruta base a la carpeta de los frames (ej: '/images/assets-travel/frame-card/').
 * @param {string} fileExtension - La extensión de los archivos (ej: 'png').
 * @param {number} [pixelsPerFrame=400] - Distancia de scroll (en píxeles) para avanzar un frame.
 */
function initScrollFrameAnimation(canvasSelector, triggerSelector, contentSelector, frameCount, basePath, fileExtension, pixelsPerFrame = 400) {
  return new Promise((resolve) => { // Envuelve la lógica en una Promise

    // --- SETUP BÁSICO ---
    const canvas = document.querySelector(canvasSelector);
    const triggerElement = document.querySelector(triggerSelector);
    const contentElement = document.querySelector(contentSelector);

    if (!canvas || !triggerElement || !contentElement) {
      console.error("No se encontraron elementos Canvas, Trigger o Content. Revisa los selectores.");
      return resolve(); // Resuelve para no bloquear si faltan elementos
    }

    const context = canvas.getContext("2d");
    const frames = [];
    const animation = { frame: 0 };

    const framesScrollDistance = (frameCount - 1) * pixelsPerFrame;
    const cardAnimationScrollDistance = pixelsPerFrame * 3;
    const totalTriggerHeight = framesScrollDistance + cardAnimationScrollDistance;

    const getFramePath = (index) => {
      // Se usa i + 1 porque los archivos van de 'frame1' a 'frameN'
      return `${basePath}frame${index + 1}.${fileExtension}`;
    };

    const drawFrame = (index) => {
      const frameIndex = Math.round(index);
      const safeIndex = Math.min(Math.max(0, frameIndex), frameCount - 1);

      if (frames[safeIndex] && frames[safeIndex].complete) {
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.drawImage(frames[safeIndex], 0, 0, canvas.width, canvas.height);
      }
    };

    // SETUP PRINCIPAL DE LA ANIMACIÓN CON TIMELINE
    const setupAnimation = () => {
      const tl = gsap.timeline({
        scrollTrigger: {
          trigger: triggerElement,
          scrub: 0.5,
          start: "top top",
          end: `+=${totalTriggerHeight}`,
          pin: true, // Esto fija el elemento y crea el espacio (placeholder)
        }
      });

      // 1. Animación de la Secuencia de Frames
      tl.to(animation, {
        frame: frameCount - 1,
        ease: "none",
        snap: "frame",
        duration: framesScrollDistance,
        onUpdate: () => drawFrame(animation.frame),
      }, 0);

      // 2. Animación del Contenido de la Card
      const startTime = framesScrollDistance * 0.9;
      gsap.set(contentElement, { transformOrigin: 'top center' });

      tl.to(contentElement, {
        opacity: 1,
        scaleY: 1,
        y: 0,
        duration: cardAnimationScrollDistance,
        ease: "power3.out"
      }, startTime);
    };

    const preloadImages = () => {
      let loadedCount = 0;

      for (let i = 0; i < frameCount; i++) {
        const img = new Image();

        img.onload = () => {
          loadedCount++;

          if (i === 0) {
            // Establecer dimensiones del canvas basadas en el primer frame
            canvas.width = img.width;
            canvas.height = img.height;
            drawFrame(0);
            canvas.style.objectFit = 'contain';
          }

          if (loadedCount === frameCount) {
            setupAnimation();
            resolve();
          }
        };

        img.onerror = () => {
          console.error('Error al cargar frame en initScrollFrameAnimation:', getFramePath(i));
          loadedCount++;
          if (loadedCount === frameCount) {
            setupAnimation();
            resolve(); // Resolver incluso en caso de error para no bloquear
          }
        };

        img.src = getFramePath(i);
        frames.push(img);
      }
    };

    preloadImages();
  });
}

/**
* Inicializa una animación de secuencia de frames en un canvas controlada por scroll,
* sincronizada con la transición de múltiples elementos de contenido (slides).
* La transición de los slides se centra alrededor de los puntos de 'pixelsPerTransition'.
* * @param {string} canvasSelector - Selector del elemento <canvas>.
* @param {string} triggerSelector - Selector del elemento que actúa como punto de inicio y pin.
* @param {string} slidesSelector - Selector de todos los contenedores de slides a animar.
* @param {number} frameCount - El número total de frames.
* @param {string} basePath - La ruta base a la carpeta de los frames.
* @param {number} pixelsPerTransition - Distancia de scroll reservada para cada sección de slide.
*/
function initTimeCanvasAnimation(canvasSelector, triggerSelector, slidesSelector, frameCount, basePath, pixelsPerTransition) {
  return new Promise((resolve) => { // Envuelve la lógica en una Promise
    // --- SELECTORES DE ELEMENTOS ---
    const canvas = document.querySelector(canvasSelector);
    const triggerElement = document.querySelector(triggerSelector);
    const slideElements = document.querySelectorAll(slidesSelector);

    // Validaciones iniciales
    if (!canvas || !triggerElement || slideElements.length === 0) {
      console.error("No se encontraron elementos Canvas, Trigger o Slides. Revisa los selectores.");
      return resolve();
    }
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
      console.error("GSAP o ScrollTrigger no están cargados. Asegúrate de incluir las librerías.");
      return resolve();
    }

    const context = canvas.getContext("2d");
    const frames = [];
    const animation = { frame: 0 };

    const slideCount = slideElements.length;
    const totalTransitions = slideCount - 1;
    // La distancia total de scroll incluye todas las secciones de transición/espera
    // y una distancia extra al final para que el último frame/slide se mantenga.
    // Usamos el número de slides completos (slideCount) por pixelsPerTransition
    // El último slide tiene una "espera" de pixelsPerTransition.
    const totalScrollDistance = (slideCount) * pixelsPerTransition;

    const TRANSITION_SCROLL_LENGTH = pixelsPerTransition * 0.25;
    const TRANSITION_START_OFFSET = TRANSITION_SCROLL_LENGTH / 2;

    const getFramePath = (index) => {
      // Usamos el índice de frame + 1 para las URLs
      return `${basePath}frame${index + 1}.jpg`;
    };

    const drawFrame = (index) => {
      const frameIndex = Math.round(index);
      const safeIndex = Math.min(Math.max(0, frameIndex), frameCount - 1);

      if (frames[safeIndex] && frames[safeIndex].complete) {
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.drawImage(frames[safeIndex], 0, 0, canvas.width, canvas.height);
      }
    };

    // SETUP PRINCIPAL DE LA ANIMACIÓN CON TIMELINE
    const setupAnimation = () => {
      // 1. Configuración inicial de los slides
      // Los slides quedan encimados: sólo el visible recibe clics (sus ligas).
      gsap.set(slideElements, {
        opacity: 0, y: 20, filter: "blur(5px)", pointerEvents: "none",
      });
      gsap.set(slideElements[0], { opacity: 1, y: 0, filter: "none", pointerEvents: "auto" });

      // 2. Creación del Timeline principal
      const tl = gsap.timeline({
        scrollTrigger: {
          trigger: triggerElement,
          scrub: 0.5,
          start: "top top",
          end: `+=${totalScrollDistance}`,
          pin: true,
        }
      });

      // 3. Animación de la Secuencia de Frames
      tl.to(animation, {
        frame: frameCount - 1,
        ease: "none",
        duration: totalScrollDistance,
        onUpdate: () => drawFrame(animation.frame),
      }, 0);

      // 4. Animación de Transición de Slides
      for (let i = 0; i < totalTransitions; i++) {
        const currentSlide = slideElements[i];
        const nextSlide = slideElements[i + 1];
        const centerTime = (i + 1) * pixelsPerTransition;
        const transitionStartTime = centerTime - TRANSITION_START_OFFSET;

        tl.to(currentSlide, {
          opacity: 0, y: -20, filter: "blur(5px)", pointerEvents: "none", duration: TRANSITION_SCROLL_LENGTH, ease: "power2.in",
        }, transitionStartTime);

        tl.fromTo(nextSlide,
          { opacity: 0, y: 20, filter: "blur(5px)", pointerEvents: "none" },
          { opacity: 1, y: 0, filter: "none", pointerEvents: "auto", duration: TRANSITION_SCROLL_LENGTH, ease: "power2.out", },
          transitionStartTime
        );
      }
    };

    // PRECARGA DE IMÁGENES
    const preloadImages = () => {
      let loadedCount = 0;

      for (let i = 0; i < frameCount; i++) {
        const img = new Image();
        img.crossOrigin = "anonymous";

        img.onload = () => {
          loadedCount++;

          if (i === 0) {
            // Establecer dimensiones del canvas basadas en el primer frame
            canvas.width = img.width;
            canvas.height = img.height;
            const container = canvas.parentElement;
            canvas.style.width = container.offsetWidth + 'px';
            canvas.style.height = container.offsetHeight + 'px';
            drawFrame(0);
          }

          if (loadedCount === frameCount) {
            setupAnimation();
            resolve();
          }
        };

        img.onerror = () => {
          console.error(`Error al cargar el frame en initTimeCanvasAnimation: ${getFramePath(i)}. Asegúrate de que las rutas sean correctas.`);
          loadedCount++;
          if (loadedCount === frameCount) {
            setupAnimation();
            resolve();
          }
        }

        img.src = getFramePath(i);
        frames.push(img);
      }
    };

    // Configuración para el redimensionamiento del canvas
    const resizeCanvas = () => {
      if (!canvas.width || !canvas.height) return;
      const container = canvas.parentElement;
      const width = container.offsetWidth;
      const height = container.offsetHeight;

      canvas.style.width = width + 'px';
      canvas.style.height = height + 'px';
      drawFrame(animation.frame);
    };
    window.addEventListener('resize', resizeCanvas);

    // Iniciar la precarga
    preloadImages();
  });
}

export function initHomeAnimations() {
  // Las carpetas de cuadros las publica cada bloque en data-frames: así el
  // superadmin puede reemplazar la secuencia sin que nadie toque este archivo.
  const banner = document.querySelector('#scroll-animation-container');
  const timeline = document.querySelector('#time-canvas-weeding');

  // asset() se come la barra final, así que la carpeta se normaliza aquí.
  const folder = (path, fallback) => (path || fallback).replace(/\/?$/, '/');

  const framesCard = folder(banner?.dataset.frames, '/images/assets-travel/frame-card');
  const framesPathTime = isLg
    ? folder(timeline?.dataset.frames, '/images/assets-travel/frame-time')
    : folder(timeline?.dataset.framesMobile, '/images/assets-travel/frame-time-mobile');

  const frameAnimationPromise = initScrollFrameAnimation(
    '#animation-canvas',
    '#scroll-animation-container',
    '#card-info',
    25,
    framesCard,
    'png',
    50
  );

  const timeAnimationPromise = initTimeCanvasAnimation(
    '#animation-canvas-time',
    '#time-canvas-weeding',
    '.slides-time',
    36,
    framesPathTime,
    1000
  );

  // Las devuelve para que el resto de animaciones esperen a que los canvas
  // terminen de precargar y de fijar sus pines (ver animations.js).
  return { frameAnimationPromise, timeAnimationPromise };
}
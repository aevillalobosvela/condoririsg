/**
 * Prevención Global de Doble Submit en Formularios
 * Evita que los usuarios envíen formularios múltiples veces por doble clic
 * Se aplica automáticamente a TODOS los formularios del sistema
 * 
 * EXCEPCIONES:
 * - Formularios con method="GET" (búsquedas/filtros)
 * - Formularios con atributo data-no-prevent-double="true"
 * - Formularios con clase "no-prevent-double"
 */
(function() {
  'use strict';
  
  document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
      // EXCEPCIÓN 1: Ignorar formularios GET (búsquedas/filtros)
      if (form.method.toLowerCase() === 'get') {
        return;
      }
      
      // EXCEPCIÓN 2: Ignorar formularios con atributo data-no-prevent-double
      if (form.dataset.noPreventDouble === 'true') {
        return;
      }
      
      // EXCEPCIÓN 3: Ignorar formularios con clase no-prevent-double
      if (form.classList.contains('no-prevent-double')) {
        return;
      }
      
      let isSubmitting = false;
      let wasPreventedByValidation = false;
      
      form.addEventListener('submit', function(e) {
        // Si ya se está enviando, cancelar el nuevo intento
        if (isSubmitting) {
          e.preventDefault();
          e.stopImmediatePropagation();
          return false;
        }
        
        // Verificar si el evento fue cancelado por otra validación
        const originalPreventDefault = e.preventDefault.bind(e);
        e.preventDefault = function() {
          wasPreventedByValidation = true;
          originalPreventDefault();
        };
        
        // Marcar como enviando
        isSubmitting = true;
        
        // Buscar todos los botones de submit en el formulario
        const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        
        submitButtons.forEach(function(btn) {
          btn.disabled = true;
          
          // Si es un botón (no input), cambiar el texto con spinner
          if (btn.tagName === 'BUTTON') {
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...';
          }
        });
        
        // Verificar si el submit fue cancelado por validación
        setTimeout(function() {
          // Si hay error de validación HTML5 o fue cancelado por JS
          if (!form.checkValidity() || wasPreventedByValidation) {
            isSubmitting = false;
            wasPreventedByValidation = false;
            submitButtons.forEach(function(btn) {
              btn.disabled = false;
              if (btn.tagName === 'BUTTON' && btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
              }
            });
          }
        }, 500);
      });
      
      // Rehabilitar si el usuario regresa con el botón "Atrás" del navegador
      window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
          isSubmitting = false;
          wasPreventedByValidation = false;
          const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
          submitButtons.forEach(function(btn) {
            btn.disabled = false;
            if (btn.tagName === 'BUTTON' && btn.dataset.originalHtml) {
              btn.innerHTML = btn.dataset.originalHtml;
            }
          });
        }
      });
    });
  });
})();

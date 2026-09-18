(function () {
  'use strict';

  var quiz = document.getElementById('quiz');
  if (!quiz) return;

  var steps = Array.prototype.slice.call(quiz.querySelectorAll('.quiz-step'));
  var total = steps.length;
  if (!total) return;

  var form = document.getElementById('quizForm');
  var bar = document.getElementById('quizBar');
  var count = document.getElementById('quizCount');
  var feedback = document.getElementById('quizFeedback');
  var feedbackIco = document.getElementById('quizFeedbackIco');
  var feedbackText = document.getElementById('quizFeedbackText');
  var action = document.getElementById('quizAction');
  var csrf = form.querySelector('input[name="csrf_token"]');

  var current = 0;
  var locked = false;
  var busy = false;

  function step(i) { return steps[i]; }

  function updateBar(checked) {
    var done = current + (checked ? 1 : 0);
    bar.style.width = Math.round((done / total) * 100) + '%';
    count.textContent = Math.min(current + 1, total) + '/' + total;
  }

  function showFeedback(ok, text) {
    feedback.hidden = false;
    feedback.classList.toggle('ok', ok);
    feedback.classList.toggle('bad', !ok);
    feedbackIco.innerHTML = ok
      ? '<i class="bi bi-check-circle-fill"></i>'
      : '<i class="bi bi-x-circle-fill"></i>';
    feedbackText.textContent = text;
  }

  function clearFeedback() {
    feedback.hidden = true;
    feedback.classList.remove('ok', 'bad');
    feedbackIco.innerHTML = '';
    feedbackText.textContent = '';
  }

  function selected(stepEl) {
    return stepEl.querySelector('.quiz-radio:checked');
  }

  function activate(i, scroll) {
    steps.forEach(function (s, idx) {
      s.classList.toggle('active', idx === i);
    });
    locked = false;
    busy = false;
    action.disabled = !selected(step(i));
    action.textContent = 'Comprobar';
    action.classList.remove('next');
    clearFeedback();
    updateBar(false);
    if (scroll !== false) {
      quiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function markOptions(stepEl, correcta, chosen) {
    var labels = stepEl.querySelectorAll('.quiz-option');
    labels.forEach(function (label, idx) {
      var value = idx + 1;
      label.classList.remove('correct', 'wrong');
      if (value === correcta) label.classList.add('correct');
      if (value === chosen && value !== correcta) label.classList.add('wrong');
    });
    stepEl.querySelectorAll('.quiz-radio').forEach(function (r) {
      r.disabled = true;
    });
  }

  function verify() {
    if (busy || locked) return;
    var stepEl = step(current);
    var chosen = selected(stepEl);
    if (!chosen) return;

    busy = true;
    action.disabled = true;
    action.textContent = 'Comprobando…';

    var body = new URLSearchParams();
    body.append('accion', 'verificar');
    body.append('csrf_token', csrf ? csrf.value : '');
    body.append('id_ejercicio', stepEl.getAttribute('data-eid'));
    body.append('opcion', chosen.value);

    fetch(quiz.getAttribute('data-check-url'), {
      method: 'POST',
      headers: { 'X-Requested-With': 'fetch' },
      body: body,
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        busy = false;
        if (!data || !data.ok) {
          action.disabled = false;
          action.textContent = 'Comprobar';
          return;
        }
        locked = true;
        markOptions(stepEl, data.correcta, parseInt(chosen.value, 10));
        if (data.correcto) {
          showFeedback(true, '¡Correcto! +10 XP');
        } else {
          showFeedback(false, 'Casi. La respuesta correcta está marcada.');
        }
        updateBar(true);
        action.disabled = false;
        if (current === total - 1) {
          action.textContent = 'Enviar respuestas';
        } else {
          action.textContent = 'Continuar';
        }
        action.classList.add('next');
      })
      .catch(function () {
        busy = false;
        action.disabled = false;
        action.textContent = 'Comprobar';
      });
  }

  function submitForm() {
    var missing = -1;
    for (var i = 0; i < total; i++) {
      if (!step(i).querySelector('.quiz-radio:checked')) {
        missing = i;
        break;
      }
    }
    if (missing >= 0) {
      current = missing;
      activate(missing);
      return;
    }
    action.disabled = true;
    action.textContent = 'Enviando…';
    form.submit();
  }

  form.addEventListener('change', function (e) {
    if (e.target.classList.contains('quiz-radio') && !locked) {
      action.disabled = false;
    }
  });

  action.addEventListener('click', function () {
    if (locked) {
      if (current === total - 1) {
        submitForm();
      } else {
        current++;
        activate(current);
      }
    } else {
      verify();
    }
  });

  activate(0, false);
})();

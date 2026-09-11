document.addEventListener('DOMContentLoaded', () => {
  // Dynamic exercise form
  const wrap = document.getElementById('ejercicios-wrap');
  const addBtn = document.getElementById('add-ejercicio');
  if (wrap && addBtn) {
    addBtn.addEventListener('click', () => {
      const n = wrap.querySelectorAll('.ejercicio-block').length + 1;
      const block = document.createElement('div');
      block.className = 'ejercicio-block card-edu p-3 mb-3';
      block.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="fw-bold">Ejercicio ${n}</h6>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.ejercicio-block').remove();renumberEjercicios();">✕</button>
        </div>
        <label class="form-label">Pregunta</label>
        <input class="form-control mb-2" name="pregunta[]" required maxlength="500">
        <div class="row g-2">
          <div class="col-md-6"><input class="form-control" name="opcion_1[]" placeholder="Opción 1" required maxlength="255"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_2[]" placeholder="Opción 2" required maxlength="255"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_3[]" placeholder="Opción 3" maxlength="255"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_4[]" placeholder="Opción 4" maxlength="255"></div>
        </div>
        <label class="form-label mt-2">Respuesta correcta</label>
        <select class="form-select" name="correcta[]" required>
          <option value="1">Opción 1</option>
          <option value="2">Opción 2</option>
          <option value="3">Opción 3</option>
          <option value="4">Opción 4</option>
        </select>`;
      wrap.appendChild(block);
    });
  }

  window.renumberEjercicios = function() {
    if (!wrap) return;
    wrap.querySelectorAll('.ejercicio-block').forEach((b, i) => {
      const h6 = b.querySelector('h6');
      if (h6) h6.textContent = 'Ejercicio ' + (i + 1);
    });
  };

  // Gemini AI generation
  const btnIA = document.getElementById('btn-generar-ia');
  if (btnIA) {
    btnIA.addEventListener('click', async () => {
      const status = document.getElementById('ia-status');
      const tema = document.getElementById('ia-tema').value;
      const cantidad = document.getElementById('ia-cantidad').value;
      const dificultad = document.getElementById('ia-dificultad').value;

      btnIA.disabled = true;
      status.style.display = '';
      status.innerHTML = '<span class="ia-loading"></span> Generando ejercicios con IA... Esto puede tardar unos segundos.';

      const fd = new FormData();
      fd.append('cantidad', cantidad);
      fd.append('dificultad', dificultad);
      fd.append('tema', tema);
      fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

      try {
        const resp = await fetch('api/gemini.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.ok && data.ejercicios) {
          fillExercises(data.ejercicios);
          status.innerHTML = '<span class="text-success fw-bold">✓ ' + data.ejercicios.length + ' ejercicios generados. Puedes editarlos antes de guardar.</span>';
        } else {
          status.innerHTML = '<span class="text-danger fw-bold">✕ ' + (data.error || 'Error desconocido.') + '</span>';
        }
      } catch (err) {
        status.innerHTML = '<span class="text-danger fw-bold">✕ Error de conexión.</span>';
      }
      btnIA.disabled = false;
    });
  }

  function fillExercises(ejercicios) {
    if (!wrap) return;
    wrap.innerHTML = '';
    ejercicios.forEach((ej, idx) => {
      const n = idx + 1;
      const block = document.createElement('div');
      block.className = 'ejercicio-block card-edu p-3 mb-3';
      block.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="fw-bold">Ejercicio ${n}</h6>
          <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.ejercicio-block').remove();renumberEjercicios();">✕</button>
        </div>
        <label class="form-label">Pregunta</label>
        <input class="form-control mb-2" name="pregunta[]" required maxlength="500" value="${escHtml(ej.pregunta)}">
        <div class="row g-2">
          <div class="col-md-6"><input class="form-control" name="opcion_1[]" placeholder="Opción 1" required maxlength="255" value="${escHtml(ej.opcion_1)}"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_2[]" placeholder="Opción 2" required maxlength="255" value="${escHtml(ej.opcion_2)}"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_3[]" placeholder="Opción 3" maxlength="255" value="${escHtml(ej.opcion_3 || '')}"></div>
          <div class="col-md-6"><input class="form-control" name="opcion_4[]" placeholder="Opción 4" maxlength="255" value="${escHtml(ej.opcion_4 || '')}"></div>
        </div>
        <label class="form-label mt-2">Respuesta correcta</label>
        <select class="form-select" name="correcta[]" required>
          <option value="1" ${ej.respuesta_correcta == 1 ? 'selected' : ''}>Opción 1</option>
          <option value="2" ${ej.respuesta_correcta == 2 ? 'selected' : ''}>Opción 2</option>
          <option value="3" ${ej.respuesta_correcta == 3 ? 'selected' : ''}>Opción 3</option>
          <option value="4" ${ej.respuesta_correcta == 4 ? 'selected' : ''}>Opción 4</option>
        </select>`;
      wrap.appendChild(block);
    });
  }

  function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }
});

const state = {
  sucursal: { id: null, nombre: "" },
  servicio: { id: null, nombre: "", duracion: "" },
  profesional: { id: null, nombre: "Sin preferencia" },
  fecha: { valor: "", display: "" },
  hora: "",
};

let currentStep = 1;
const totalSteps = 4;

// Inicializar todos los listeners cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", () => {
  initSelectableCards();
  initDateCards();
  initTimeSlots();
  initFormSubmit();
  updateNav(); // empiezs la barra de navegación en el paso 1
});

function initSelectableCards() {
  document.querySelectorAll(".selectable-card[data-type]").forEach((card) => {
    card.addEventListener("click", () => {
      const type = card.dataset.type;
      const id = card.dataset.id;
      const nombre =
        card.dataset.nombre ||
        card.querySelector(".card-title")?.textContent ||
        "";

      document
        .querySelectorAll(`.selectable-card[data-type="${type}"]`)
        .forEach((c) => c.classList.remove("selected"));

      card.classList.add("selected");

      if (state[type]) {
        state[type].id = id;
        state[type].nombre = nombre;
        if (type === "servicio") {
          state[type].duracion = card.dataset.duracion || "";
        }
      }

      const inputHidden = document.getElementById(`inp_${type}_id`);
      if (inputHidden) inputHidden.value = id;

      updateSidebar();
    });
  });
}

function initDateCards() {
  document.querySelectorAll(".date-card").forEach((dc) => {
    dc.addEventListener("click", () => {
      document
        .querySelectorAll(".date-card")
        .forEach((d) => d.classList.remove("selected"));
      dc.classList.add("selected");

      state.fecha.valor = dc.dataset.fecha;
      state.fecha.display = dc.dataset.display;

      const inpFecha = document.getElementById("inp_fecha");
      if (inpFecha) inpFecha.value = state.fecha.valor;

      updateSidebar();
      simulateTimeRefresh();
    });
  });
}

function initTimeSlots() {
  document.querySelectorAll(".time-slot:not(.unavailable)").forEach((ts) => {
    ts.addEventListener("click", () => {
      document
        .querySelectorAll(".time-slot")
        .forEach((t) => t.classList.remove("selected"));

      ts.classList.add("selected");

      state.hora = ts.dataset.hora;

      const inpHora = document.getElementById("inp_hora");
      if (inpHora) inpHora.value = state.hora;

      updateSidebar();
    });
  });
}

function simulateTimeRefresh() {
  document
    .querySelectorAll(".time-slot")
    .forEach((ts) => ts.classList.remove("selected"));
  state.hora = "";
  const inpHora = document.getElementById("inp_hora");
  if (inpHora) inpHora.value = "";
  updateSidebar();
}

function updateSidebar() {
  const setVal = (id, val, empty) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (val) {
      el.textContent = val;
      el.classList.remove("empty");
    } else {
      el.textContent = empty || "Sin seleccionar";
      el.classList.add("empty");
    }
  };

  // Actzar side
  setVal("sb-sucursal", state.sucursal.nombre, "Sin seleccionar");
  setVal("sb-servicio", state.servicio.nombre, "Sin seleccionar");
  setVal("sb-duracion", state.servicio.duracion, "—");
  setVal("sb-profesional", state.profesional.nombre, "Sin preferencia");
  setVal("sb-fecha", state.fecha.display, "Sin seleccionar");
  setVal("sb-hora", state.hora, "Sin seleccionar");

  // Actualizar Paso 4 (Confirmación)
  const cf = (id, val) => {
    const e = document.getElementById(id);
    if (e) e.textContent = val || "—";
  };

  cf("conf-sucursal", state.sucursal.nombre);
  cf(
    "conf-servicio",
    state.servicio.nombre +
      (state.servicio.duracion ? ` (${state.servicio.duracion})` : ""),
  );
  cf("conf-profesional", state.profesional.nombre);
  cf("conf-fecha", state.fecha.display);
  cf("conf-hora", state.hora);
}

function goToStep(n) {
  if (n < 1 || n > totalSteps) return;
  if (n > currentStep && !validateCurrentStep()) return;

  for (let i = 1; i <= totalSteps; i++) {
    const tab = document.getElementById(`step-tab-${i}`);
    const bub = document.getElementById(`bubble-${i}`);

    if (tab) tab.classList.remove("active", "done");

    if (i < n) {
      if (tab) tab.classList.add("done");
      if (bub) bub.innerHTML = "✓";
    } else if (i === n) {
      if (tab) tab.classList.add("active");
      if (bub) bub.innerHTML = i;
    } else {
      if (bub) bub.innerHTML = i;
    }
  }

  // Mostrar sección activa
  document
    .querySelectorAll(".form-section")
    .forEach((s) => s.classList.remove("visible"));
  const currentSection = document.getElementById(`section-${n}`);
  if (currentSection) currentSection.classList.add("visible");

  currentStep = n;
  updateNav();

  // Alertas del paso final
  const alertBox = document.getElementById("confirm-alert");
  if (alertBox) {
    alertBox.style.display = n === 4 ? "flex" : "none";
  }

  if (n === 4) updateSidebar();

  window.scrollTo({ top: 0, behavior: "smooth" });
}

function nextStep() {
  goToStep(currentStep + 1);
}
function prevStep() {
  goToStep(currentStep - 1);
}

function updateNav() {
  const back = document.getElementById("btn-back");
  const next = document.getElementById("btn-next");
  const submit = document.getElementById("btn-submit");
  const counter = document.getElementById("step-counter");

  if (back) back.style.visibility = currentStep > 1 ? "visible" : "hidden";
  if (counter) counter.textContent = `Paso ${currentStep} de ${totalSteps}`;

  if (next && submit) {
    if (currentStep === totalSteps) {
      next.style.display = "none";
      submit.style.display = "inline-flex";
    } else {
      next.style.display = "inline-flex";
      submit.style.display = "none";
    }
  }
}

function validateCurrentStep() {
  if (currentStep === 1) {
    if (!state.sucursal.id) {
      showToast("⚠️ Seleccioná una sucrsal para continuar.", "warn");
      return false;
    }
    if (!state.servicio.id) {
      showToast("⚠️ Seleccioná un servicio para continuar.", "warn");
      return false;
    }
  }
  if (currentStep === 3) {
    if (!state.fecha.valor) {
      showToast("⚠️ Seleccioná una fecha para continuar.", "warn");
      return false;
    }
    if (!state.hora) {
      showToast("⚠️ Seleccioná  horario para continuar.", "warn");
      return false;
    }
  }
  return true;
}

function showToast(msg, type = "info") {
  let toast = document.getElementById("toast");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "toast";
    toast.style.cssText = `
            position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%) translateY(80px);
            background: var(--azul-profundo, #0f172a); color: #fff; padding: .8rem 1.4rem;
            border-radius: 50px; font-size: .875rem; font-weight: 600;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); z-index: 999; 
            transition: transform .3s ease, opacity .3s ease;
            opacity: 0; max-width: 90vw; text-align: center;
        `;
    document.body.appendChild(toast);
  }

  if (type === "warn") toast.style.background = "#D97706";
  else if (type === "ok") toast.style.background = "var(--verde-ok, #10b981)";
  else toast.style.background = "var(--azul-profundo, #0f172a)";

  toast.textContent = msg;
  toast.style.transform = "translateX(-50%) translateY(0)";
  toast.style.opacity = "1";

  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => {
    toast.style.transform = "translateX(-50%) translateY(80px)";
    toast.style.opacity = "0";
  }, 3200);
}

function initFormSubmit() {
  document
    .getElementById("formAgenda")
    ?.addEventListener("submit", function (e) {
      if (
        !state.sucursal.id ||
        !state.servicio.id ||
        !state.fecha.valor ||
        !state.hora
      ) {
        e.preventDefault();
        showToast("⚠️ Completá todos los campos requeridos.", "warn");
        return;
      }

      const btn = document.getElementById("btn-submit");
      if (btn) {
        btn.disabled = true;
        btn.textContent = "Enviando…";
      }
    });
}

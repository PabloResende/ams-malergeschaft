// public/js/inventory_control.js

document.addEventListener('DOMContentLoaded', () => {
    const reason     = document.getElementById('reasonSelect');
    const customDiv  = document.getElementById('customReasonDiv');
    const projectDiv = document.getElementById('projectSelectDiv');
    const form       = document.getElementById('controlForm');
    const itemsData  = document.getElementById('itemsData');
  
    // Exibe/esconde campos de motivo
    reason.addEventListener('change', () => {
      customDiv.classList.toggle('hidden', reason.value !== 'outros');
      projectDiv.classList.toggle('hidden', reason.value !== 'projeto');
    });
  
    // Habilita input de quantidade se marcado
    document.querySelectorAll('.item-checkbox').forEach(cb => {
      const qty = cb.parentElement.querySelector('.qty-input');
      cb.addEventListener('change', () => {
        qty.disabled = !cb.checked;
      });
    });
  
    // Antes de enviar, monta JSON de {item_id: quantity}
    form.addEventListener('submit', () => {
      const data = {};
      document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const id = cb.value;
        const qty = parseInt(cb.parentElement.querySelector('.qty-input').value, 10) || 1;
        data[id] = qty;
      });
      itemsData.value = JSON.stringify(data);
    });
  });
  
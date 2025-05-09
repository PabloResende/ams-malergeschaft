document.addEventListener('DOMContentLoaded', () => {
    const addBtn         = document.getElementById('addTransactionBtn');
    const modal          = document.getElementById('transactionModal');
    const closeBtn       = document.getElementById('closeTransactionModal');
    const cancelBtn      = document.getElementById('cancelTransaction');
    const typeSelect     = document.getElementById('txTypeSelect');
    const dueContainer   = document.getElementById('dueDateContainer');
    const categorySelect = modal.querySelector('select[name="category_id"]');
  
    // copia opções originais (com data-type)
    const originalOptions = Array.from(categorySelect.options).map(opt => ({
      value: opt.value,
      text: opt.textContent,
      type: opt.getAttribute('data-type')
    }));
  
    function openModal() {
      modal.classList.remove('hidden');
    }
  
    function closeModal() {
      modal.classList.add('hidden');
    }
  
    addBtn.addEventListener('click', openModal);
    [closeBtn, cancelBtn].forEach(btn => btn.addEventListener('click', closeModal));
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
  
    function toggleDueDate() {
      if (typeSelect.value === 'debt') dueContainer.classList.remove('hidden');
      else dueContainer.classList.add('hidden');
    }
  
    function filterCategories() {
      const selType = typeSelect.value;
      categorySelect.innerHTML = '';
      originalOptions.forEach(opt => {
        if (!selType || opt.type === selType) {
          const o = document.createElement('option');
          o.value = opt.value;
          o.textContent = opt.text;
          categorySelect.appendChild(o);
        }
      });
    }
  
    typeSelect.addEventListener('change', () => {
      toggleDueDate();
      filterCategories();
    });
  
    // inicialização
    toggleDueDate();
    filterCategories();
  });
  
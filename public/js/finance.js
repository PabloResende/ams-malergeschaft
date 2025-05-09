
document.addEventListener('DOMContentLoaded', () => {
    const addBtn       = document.getElementById('addTransactionBtn');
    const modal        = document.getElementById('transactionModal');
    const closeBtn     = document.getElementById('closeTransactionModal');
    const cancelBtn    = document.getElementById('cancelTransaction');
    const typeSelect   = document.getElementById('txTypeSelect');
    const dueContainer = document.getElementById('dueDateContainer');
  
    // Abre o modal
    addBtn.addEventListener('click', () => {
      modal.classList.remove('hidden');
    });
  
    // Fecha o modal
    [closeBtn, cancelBtn].forEach(btn =>
      btn.addEventListener('click', () => {
        modal.classList.add('hidden');
      })
    );
  
    // Exibe campo de vencimento apenas para 'debt'
    const toggleDueDate = () => {
      if (typeSelect.value === 'debt') {
        dueContainer.classList.remove('hidden');
      } else {
        dueContainer.classList.add('hidden');
      }
    };
    typeSelect.addEventListener('change', toggleDueDate);
  
    // Caso já venha selecionado (se em edição), ajustar visibilidade
    toggleDueDate();
  });
  
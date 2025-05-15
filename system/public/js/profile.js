document.addEventListener("DOMContentLoaded", function() {
    const phone = document.getElementById('phone');
    const cpf = document.getElementById('cpf');

    phone.addEventListener("input", function() {
        this.value = this.value.replace(/\D/g, "").replace(/^(\d{2})(\d)/g, "($1) $2").replace(/(\d{5})(\d)/, "$1-$2");
    });

    if(cpf){
      cpf.addEventListener("input", function() {
          this.value = this.value.replace(/\D/g, "").replace(/(\d{3})(\d)/, "$1.$2")
                                .replace(/(\d{3})(\d)/, "$1.$2")
                                .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
      });
    }
});
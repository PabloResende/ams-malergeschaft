document.addEventListener("DOMContentLoaded", function () {
  // Controle do menu de idiomas
  const langButton = document.getElementById("language-button");
  const langMenu = document.getElementById("language-menu");

  if (langButton) {
    langButton.addEventListener("click", (event) => {
      event.stopPropagation();
      langMenu.classList.toggle("hidden");
    });
  }

  document.addEventListener("click", (event) => {
    if (
      langButton &&
      !langButton.contains(event.target) &&
      !langMenu.contains(event.target)
    ) {
      langMenu.classList.add("hidden");
    }
  });

  // Controle das notificações
  const notificationBtn = document.getElementById("notificationBtn");
  const notificationList = document.getElementById("notificationList");
  const notificationDot = document.getElementById("notificationDot");

  if (notificationBtn && notificationList && notificationDot) {
    notificationBtn.addEventListener("click", function () {
      notificationList.classList.toggle("hidden");
      notificationDot.classList.add("hidden");
    });

    // Verifica se há notificações e exibe o ponto vermelho
    const hasNotifications =
      document.querySelectorAll("#notificationList ul li").length > 1;
    if (hasNotifications) {
      notificationDot.classList.remove("hidden");
    } else {
      notificationDot.classList.add("hidden");
    }
  }
});

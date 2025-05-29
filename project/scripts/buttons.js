$(document).ready(function() {
    // Инициализация аккордеона
    $(".b-accordion").each(function() {
        $(this).find("div:first").addClass("active");
        $(this).find("h3:first").addClass("active-acc-text");
        $(this).find("p:not(:first)").hide();
    });

    // Обработчик клика
    $(".b-accordion h3").click(function(e) {
        // Не обрабатываем клики на элементах формы
        if ($(e.target).is('input, select, textarea, label, a')) {
            return;
        }

        const $accordionItem = $(this).closest(".b-accordion div");
        
        // Закрываем другие элементы
        $accordionItem.siblings("div").removeClass("active")
            .find("p").slideUp("slow")
            .prev("div").find("h3").removeClass("active-acc-text");

        // Открываем/закрываем текущий
        $accordionItem.toggleClass("active")
            .find("p").slideToggle("slow")
            .prev("div").find("h3").toggleClass("active-acc-text");
    });
});

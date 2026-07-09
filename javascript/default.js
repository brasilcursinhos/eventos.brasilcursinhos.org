$(function() {
    $("#link-about-us").on("click", function(){
        window.location.href = "/sobre-nos";
    });

    $("#link-selective-process").on("click", function(){
        window.location.href = "/processos-seletivos";
    });

    $("#link-events").on("click", function(){
        window.location.href = "/eventos";
    });

    $(".btn-expand-sidebar").on("click", function(){
        if($(".btn-expand-sidebar").hasClass("collapsed")) {
            $(".main-content").removeClass("d-none");
        } else {
            $(".main-content").addClass("d-none");
        }
    });
});

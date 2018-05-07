
    $(".art-buscar").button().on("click", function (event) {
        event.stopPropagation();
        event.preventDefault();

        $('#paginabuscar').val(1);
        buscarArticulos();

    });


  

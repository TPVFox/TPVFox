function  ObtenerDatosFamilia(){
    $('#nombreFamilia').val($('#inputnombre').val());
    $('#inputpadreWeb #combopadre').val($('#inputPadre #combopadre').val());
}
$(function () {
    $("#combopadre").combobox({
        select: function (event, ui) {
            console.log(ui.item.value);
            $('#inputidpadre').val(ui.item.value);
        },
    });

});

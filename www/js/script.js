var glb = {};
glb.ajax_count = 0;
glb.ajax_error = false;
$(document).ready(function() {
    
});
// ---------------- //
//  HELP FUNCTIONS  //
// ---------------- //
function closest(elem, selector) {
    if (elem instanceof jQuery) ;
    else elem = $(elem);
    while (elem.length && !elem.is(selector) && !elem.has(selector).length)
        elem = elem.parent();
    if (elem.length && elem.is(selector)) return elem;
    else return elem.find(selector);
}

function jeval(obj) {
    try {
        return eval('(' + obj + ')');
    } catch (e) {
        return null;
    }
}

function mt_rand(min, max) {
    return Number(Math.floor(Math.random() * (max - min + 1)) + min);
}

function ajax(url, data, context, handle) {
    if (glb.ajax_error) return;
    glb.ajax_count += 1;
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'text',
        async: true,
        cache: false,
        data: data,
        context: context,
        error: function () {
            glb.ajax_error = true;
        },
        success: function (text) {
            if (glb.ajax_error) return;
            glb.ajax_count -= 1;
            var obj = jeval(text);
            if (!obj) {
                console.log(text);
                return;
            }
            if (handle) handle(obj, this);
        },
    });
}

function load_css(url) {
    var link = document.createElement('link');
    document.getElementsByTagName('head')[0].appendChild(link);
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = url;
}

function load_js(url, onload) {
    var script = document.createElement('script');
    document.getElementsByTagName('head')[0].appendChild(script);
    script.type = 'application/javascript';
    if (typeof onload != 'undefined' && typeof window[onload] == 'function')
        script.onload = window[onload];
    script.src = url;
}


// function initUploadZone(selector) {
    
// }
// function() {
//         var context = this
//         var span = $(this).closest('*:has(.dropZone)').find('.dropZone span:eq(0)')
//         $('form.editor input[name=upload]').val(1)
//         $('form.editor').ajaxSubmit({
//             beforeSend: function() {
//                 span.text('Загрузка... 0%')
//             },
//             uploadProgress: function(event, position, total, percentComplete) {
//                 span.text('Загрузка... ' + percentComplete + '%')
//             },
//             success: function(obj) {
//                 obj = jeval(obj)
//                 if (!obj) return
//                 if (obj.echo) {
//                     var ref = $(context).closest('*:has(.dropZoneFiles)').find('.dropZoneFiles')
//                     ref.find('.dropzone-php').remove()
//                     ref.prepend(obj.echo)
//                 }
//                 if (obj.id) {
//                     var ref = $(context)
//                         .closest('*:has(input:hidden[name=id])')
//                         .find('input:hidden[name=id]')
//                         .val(obj.id)
//                 }
//             }
//         })
//         $('form.editor input[name=upload]').val(0)
//     }
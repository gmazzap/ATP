(function ($, ATP) {

    "use strict"

    ATP.methods = {};
    ATP.map = {ids: [], spans: {}, slugs: {}, posts: {}};

    ATP.methods.checkResponse = function (response) {
        return typeof response.success !== 'undefined'
            && response.success
            && typeof response.data === 'object';
    };

    ATP.methods.onError = function () {
        $(ATP.map.ids).each(function (id, el) {
            $('#' + el).remove();
        });
        $(document).trigger('ajax_template_part_error', ATP.map);
        $(document).trigger('ajax_template_part_done');

    };

    ATP.methods.onSuccess = function (data) {
        $.each(ATP.map.ids, function (id, el) {
            var $el = $('#' + el);
            if ($el.length && typeof data[el] === 'string') {
                $el.replaceWith(data[el]).show();
            } else {
                $el.remove();
            }
        });
        $(document).trigger('ajax_template_part_success', ATP.map);
        $(document).trigger('ajax_template_part_done');
    };

    ATP.methods.callAjax = function () {
        return $.ajax({
            url: ATP.info.ajax_url,
            dataType: 'json',
            type: 'POST',
            data: {
                action: 'ajaxtemplatepart',
                query_data: ATP.info.query_data,
                files_data: ATP.map.slugs,
                posts_data: ATP.map.posts
            }
        });
    };

    ATP.methods.parseDom = function () {
        $('span[data-ajaxtemplate]').each(function (i, el) {
            var $el = $(el);
            var id = $el.attr('id');
            var rawData = decodeURIComponent($el.data('ajaxtemplate').replace(/\+/g, ' '));
            var templateData = $.parseJSON(rawData);
            var name = templateData.name;
            var slug = templateData.slug;
            var post = $el.data('post');
            if (typeof id === 'string' && id !== '' && typeof name === 'string' && name !== '') {
                if (typeof slug !== 'string') {
                    slug = false;
                }
                if (Number(post) <= 0) {
                    post = false;
                }
                ATP.map.ids.push(id);
                ATP.map.spans[id] = $el;
                ATP.map.slugs[id] = [name, slug];
                ATP.map.posts[id] = post;
            }
            $el.data('ajaxtemplate', null);
        });
    };

    ATP.methods.update = function () {
        if (ATP.map.ids.length < 1) {
            return false;
        }
        ATP.methods
            .callAjax()
            .done(function (response) {
                if (ATP.methods.checkResponse(response)) {
                    ATP.methods.onSuccess(response.data);
                } else {
                    ATP.methods.onError();
                }
            })
            .fail(function () {
                ATP.methods.onError();
            });
    };

    $(document).ready(function () {
        ATP.methods.parseDom();
        ATP.methods.update();
    });

    $(document).on('ajax_template_part_done', function () {
        ATP.map = {ids: [], spans: {}, slugs: {}, posts: {}};
    });

})(jQuery, AjaxTemplatePartData);

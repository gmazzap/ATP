(function ($, ATP) {

    ATP.methods = {};
    ATP.map = {spans: [], slugs: []};

    ATP.methods.checkResponse = function (response) {
        return typeof response.success !== 'undefined'
                && response.success
                && typeof response.data === 'object';
    };

    ATP.methods.onError = function () {
        $(ATP.map.spans).each(function (id, $el) {
            $el.remove();
        });
        $(document).trigger('ajax_template_part_error', ATP.map);
        $(document).trigger('ajax_template_part_done');

    };

    ATP.methods.onSuccess = function (data) {
        $(ATP.map.spans).each(function (id, $el) {
            if (typeof data[id] === 'string') {
                $el.replace(data[id]);
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
                files_data: ATP.map.slugs
            }
        });
    };

    ATP.methods.parseDom = function () {
        $('span[data-ajaxtemplatename]').each(function (i, el) {
            var $el = $(el);
            var id = $(el).attr('id');
            var name = $el.data('ajaxtemplatename');
            var slug = $el.data('ajaxtemplateslug');
            if (typeof id === 'string' && id !== '' && typeof name === 'string' && name !== '') {
                if (typeof slug !== 'string') {
                    slug = false;
                }
                ATP.map.spans[id] = $el;
                ATP.map.slugs[id] = [name, slug];
            }
            $el.data('ajaxtemplatename', null);
            $el.data('ajaxtemplateslug', null);
        });
    };

    ATP.methods.update = function () {
        if (ATP.map.spans.length < 1 || ATP.map.spans.length !== ATP.map.slugs.length) {
            return false;
        }
        ATP.methods.callAjax().done(function (response) {
            if (ATP.methods.checkResponse(response)) {
                ATP.methods.onSuccess(response.data);
            } else {
                ATP.methods.onError();
            }
        }).fail(function () {
            ATP.methods.onError();
        });
    };

    $(document).ready(function () {
        ATP.methods.parseDom();
        ATP.methods.update();
    });

    $(document).on('ajax_template_part_done', function () {
        ATP.map = {spans: [], slugs: []};
    });

})(jQuery, AjaxTemplatePartData);


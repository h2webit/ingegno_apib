$(function () {
    'use strict';
    var token = JSON.parse(atob($('body').data('csrf')));
    var token_name = token.name;
    var token_hash = token.hash;

    /*
     * Tree view loading
     */
    var form = $('#post-form');
    var list = $('#post-list');

    /*
     * Autogrowing textarea
     */
    $('[name=message]', form).keyup(function (e) {
        var txa = $(this);
        while (txa.outerHeight() < this.scrollHeight + parseFloat(txa.css('borderTopWidth')) + parseFloat(txa.css('borderBottomWidth'))) {
            txa.height(txa.height() + 1);
        }
    });

    /*
     * Like / +1
     */
    $(list).on('click', '.js-like', function () {
        var btn = $(this);
        var postId = btn.data('post');
        var likeId = parseInt(btn.data('like'));

        if (isNaN(likeId) || likeId < 1) {
            $.ajax({
                url: base_url + 'firecrm/socialwall/addLike',
                type: 'POST',
                data: { [token_name]: token_hash, post_id: postId },
                async: false,
                success: function (response, textStatus, jqXHR) {
                    if (parseInt(response.status) === 1) {
                        $('.js-num-likes-' + postId).text(response.txt.socialwall_posts_likes);
                        btn.addClass('bg-blue').removeClass('bg-navy').data('like', parseInt(response.data.socialwall_likes_id));
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                },
            });
        } else {
            // Do unlike
            $.getJSON(
                base_url + 'firecrm/socialwall/removeLike/' + likeId,
                function (out) {
                    if (parseInt(out.status) === 1) {
                        var likeLabel = $('.js-num-likes-' + postId);
                        likeLabel.text(parseInt(likeLabel.text().trim()) - 1);
                        btn.addClass('bg-navy').removeClass('bg-blue').data('like', null);
                        toastr;
                    } else {
                        toastr.error(out.txt);
                    }
                },
                'json'
            );
        }
    });

    /*
     * Gestione preview upload
     */
    var chooseContainer = $('.upload-choose', form);
    var previewContainer = $('.upload-preview', form);
    var input = $('[name=socialwall_posts_file]', chooseContainer);
    var preview = $('img', previewContainer);

    input.change(function () {
        var input = this;

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                preview.attr('src', e.target.result);
                previewContainer.removeClass('hide');
                chooseContainer.addClass('hide');
            };

            reader.readAsDataURL(input.files[0]);
        }
    });

    preview.click(function () {
        preview.attr('src', '');
        input.val('');
        previewContainer.addClass('hide');
        chooseContainer.removeClass('hide');
    });

    /*
     * Comment form actions
     */
    $('#post-form, .comment-form').on('form-ajax-success', function (event, msg) {
        if (msg.action) {
            window.location.assign(window.location.href.replace(window.location.search, ''));
        } else {
            insertPost(msg.post, msg.data);
            window.location.assign(window.location.href.replace(window.location.search, ''));
        }
    });

    function insertPost(postId, postView) {
        var $oldView = $('#post-' + postId);
        var $view = $(postView);

        if ($oldView.size() > 0) {
            $view = $oldView.html($view.html());
        } else {
            list.prepend($view);
        }

        $view.find('.comment-form').on('form-ajax-success', function (event, msg) {
            insertPost(msg.post, msg.view);
        });
    }

    $('.js_order_image').on('change', function () {
        var order = $(this).val();

        if (!$.isNumeric(order)) {
            alert('Order must be a number');
        } else {
            var image_id = $(this).data('image_id');

            $.ajax({
                method: 'post',
                url: base_url + 'firecrm/socialwall/orderFile',
                data: {
                    image_id: image_id,
                    order: order,
                    [token_name]: token_hash,
                },
                dataType: 'json',
                success: function (data) {
                    if (data.status == 1) {
                        toastr.success('Ordered successfully', {
                            timeOut: 2500,
                        });
                        $('.js_order_image_group').addClass('has-success');

                        setTimeout(function () {
                            $('.js_order_image_group').removeClass('has-success');
                        }, 2500);
                    } else {
                        toastr.error('Error while ordering media', {
                            timeOut: 2500,
                        });
                    }
                },
            });
        }
    });

    $('.delete-image').on('click', function () {
        if (confirm('Are you sure to delete this post?')) {
            var id = $(this).data('id');

            $.ajax({
                method: 'post',
                url: base_url + 'firecrm/socialwall/removeFile',
                data: {
                    id: id,
                    [token_name]: token_hash,
                },
                dataType: 'json',
                success: function (data) {
                    if (data.status === 1) {
                        $('.delete-image[data-id=' + id + ']')
                            .parent()
                            .remove();
                    } else {
                        toastr.error('Internal Error');
                    }
                },
            });
        }
    });

    function getUrlVars() {
        var vars = [];
        var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
            vars.push({
                param: key,
                value: value,
            });
        });
        return vars;
    }

    var params = getUrlVars(window.location.href);

    $('#view_posts').on('change', function () {
        var view_posts = $('#view_posts').val();

        if (params.length !== 0 && params[0]['param'] == 'category') {
            window.location.href = '?show=' + view_posts + '&' + params[0]['param'] + '=' + params[0]['value'];
        } else {
            window.location.href = '?show=' + view_posts;
        }
    });

    $('#cat_posts').on('change', function () {
        var cat_posts = $('#cat_posts').val();

        if (cat_posts == 0) {
            if (params.length !== 0 && params[0]['param'] == 'show') {
                window.location.href = '?' + params[0]['param'] + '=' + params[0]['value'];
            } else {
                window.location = window.location.pathname;
            }
        } else {
            if (params.length !== 0 && params[0]['param'] == 'show') {
                window.location.href = '?' + params[0]['param'] + '=' + params[0]['value'] + '&category=' + cat_posts;
            } else {
                window.location.href = '?category=' + cat_posts;
            }
        }
    });
});

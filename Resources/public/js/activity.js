$(function() {
    $(document).on('click', '.view-email-body-btn', function (e) {
        new Oro.widget.DialogView({
            url: $(this).attr('href'),
            dialogOptions: {
                allowMaximize: true,
                allowMinimize: true,
                dblclick: 'maximize',
                maximizedHeightDecreaseBy: 'minimize-bar',
                width : 1000,
                title: $(this).attr('title')
            }
        }).render();

        return false;
    });
});

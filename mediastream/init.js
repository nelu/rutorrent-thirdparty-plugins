plugin.loadMainCSS();

theWebUI.VPLAY = {
    stp: 'plugins/mediastream/view.php',
    play: function(target) {

        theWebUI.fManager.action.request('action=sess',
            function(data) {
                if (theWebUI.fManager.isErr(data.errcode)) {
                    log('Play failed');
                    return false;
                }
                theWebUI.fManager.makeVisbile('VPLAY_diag');
                try {

                    var vidUrl = theWebUI.VPLAY.stp + '?ses=' +
                        encodeURIComponent(data.sess) + '&dir=' +
                        encodeURIComponent(theWebUI.fManager.curpath) +
                        '&target=' + encodeURIComponent(target) + '&action=view';

                    theWebUI.VPLAY.source.src = vidUrl;
                    theWebUI.VPLAY.player.load();
                    theWebUI.VPLAY.player.play();
                } catch (err) {
                }
            });
    },

    stop: function() {
        try {
            this.player.Stop();
        } catch (err) {
        }
    },
};

plugin.flmMenu = theWebUI.fManager.flmSelect;
theWebUI.fManager.flmSelect = function(e, id) {
    plugin.flmMenu.call(this, e, id);
    if (plugin.enabled) {

        var el = theContextMenu.get(theUILang.fOpen);
        var target = id.split('_flm_')[1];

        if (el &&
            theWebUI.fManager.getExt(target).match(/^(mp4|avi|divx|mkv)$/i)) {
            theContextMenu.add(el, [CMENU_SEP]);
            theContextMenu.add(el, [
                theUILang.fView, function() {
                    theWebUI.VPLAY.play(target);
                }]);
            theContextMenu.add(el, [CMENU_SEP]);
        }
    }
};

plugin.onLangLoaded = function() {
    injectScript('plugins/mediastream/settings.js.php');

    var pd = '<div class="cont fxcaret">' +
        '<video id="vid_player" width="720" height="480" controls>' +
        '<source id="vid_src" src="" type="video/mp4">' +
        'Your browser does not support the video tag.' +
        '</video>';

    theDialogManager.make('VPLAY_diag', theUILang.mediastream, pd, false);
    theWebUI.VPLAY.player = document.getElementById('vid_player');
    theWebUI.VPLAY.source = document.getElementById('vid_src');
    theDialogManager.setHandler('VPLAY_diag', 'afterHide', 'theWebUI.VPLAY.stop()');
};

plugin.onRemove = function() {
    theWebUI.VPLAY.stop();
    $('#VPLAY_diag').remove();
};

plugin.loadLang(true);

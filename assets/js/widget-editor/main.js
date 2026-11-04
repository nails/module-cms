import '../../sass/widget-editor.scss';
import { createApp } from 'vue';
import EditorApp from './EditorApp.vue';

let app = null;
let host = null;

/**
 * Opens the editor over the page. Called by the v1 plugin when this session's version is v2.
 * @param {Object} options
 */
function open(options) {
    close();
    host = document.createElement('div');
    document.body.appendChild(host);
    app = createApp(EditorApp, { session: options });
    app.mount(host);
}

function close() {
    if (app) {
        app.unmount();
        app = null;
    }
    if (host) {
        host.remove();
        host = null;
    }
}

window.NAILS_CMS_WIDGET_EDITOR = { open, close };

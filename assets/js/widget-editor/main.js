import '../../sass/widget-editor.scss';
import { createApp } from 'vue';
import EditorApp from './EditorApp.vue';
import FieldControl from './FieldControl.vue';
import Modal from './Modal.vue';
import { registerBuiltinFields } from './builtin-fields.js';
import { installQueue, registerFieldType } from './field-types.js';

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

window.NAILS_CMS_WIDGET_EDITOR = {
    open,
    close,
    registerField: registerFieldType,
    // For field controls in other bundles: FieldControl draws nested fields, Modal is the editor's dialog
    components: { FieldControl, Modal },
};

// The built-ins first, so a module's definition for the same type replaces them
registerBuiltinFields();
installQueue(window);

'use strict';

import '../sass/admin.scss';
import WidgetEditor from './components/WidgetEditor.js';
import PageEditor from './components/PageEditor.js';
import MenuEditor from './components/MenuEditor.js';

(function() {
    window.NAILS.ADMIN.registerPlugin(
        'nails/module-cms',
        'WidgetEditor',
        function(controller) {
            return new WidgetEditor(controller);
        }
    );
    window.NAILS.ADMIN.registerPlugin(
        'nails/module-cms',
        'PageEditor',
        function(controller) {
            return new PageEditor(controller);
        }
    );
    window.NAILS.ADMIN.registerPlugin(
        'nails/module-cms',
        'MenuEditor',
        function(controller) {
            return new MenuEditor(controller);
        }
    );
})();

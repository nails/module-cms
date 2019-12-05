'use strict';

import '../sass/admin.scss';
import WidgetEditor from './components/WidgetEditor.js';

(function() {
    window.NAILS.ADMIN.registerPlugin(
        'nails/module-cms',
        'WidgetEditor',
        new WidgetEditor(window.NAILS.ADMIN)
    );
})();

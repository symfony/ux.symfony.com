import './styles/app.css';

// start the Stimulus application
import './bootstrap.js';

// Register EditorJS tool classes for the symfony/ux-editor-editorjs bridge controller.
// Bridge resolves names declared in EditorJSConfig::tools against window.UXEditorJSTools.
import Paragraph from '@editorjs/paragraph';
import Header    from '@editorjs/header';
import List      from '@editorjs/list';
import Image     from '@editorjs/image';
import Quote     from '@editorjs/quote';

window.UXEditorJSTools = { Paragraph, Header, List, Image, Quote };

import Alpine from 'alpinejs';
import { toast } from './lib/toast';
import { request } from './lib/http';
import registerComponents from './components/index';

/**
 * Global surface, deliberately kept to three things:
 *   aegis.toast(message, type)   – transient feedback
 *   aegis.request(url, options)  – fetch with CSRF + JSON handling
 *   Alpine                       – declarative behaviour in Blade
 */
window.aegis = { toast, request };
window.Alpine = Alpine;

registerComponents(Alpine);

Alpine.start();

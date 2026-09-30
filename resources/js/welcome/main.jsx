import { createRoot } from 'react-dom/client';
import App from './App';
import './welcome.css';

// Server-rendered config from welcome.blade.php: locale plus the translated strings
// listed in strings.json. Keys are the Indonesian source text, so a missing
// translation falls back to readable Indonesian rather than a blank.
const cfg = window.__ETRACE_WELCOME__ || { t: {}, locale: 'id', year: new Date().getFullYear(), langUrls: {} };

const t = (key) => {
    const value = cfg.t[key];
    if (value === undefined && import.meta.env.DEV) console.warn('[welcome] string not in strings.json:', key);
    return value ?? key;
};

createRoot(document.getElementById('welcome-root')).render(
    <App t={t} year={cfg.year} locale={cfg.locale} langUrls={cfg.langUrls} />,
);

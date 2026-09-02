import axios from "axios";

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common['Accept'] = 'application/json'

//-------------------------------------------------------

import { applyTheme } from "./Composables/useDarkMode";
import settings from "./data/settings";

applyTheme(localStorage.getItem('darkMode') ?? settings.preferences.darkMode)

//-------------------------------------------------------

import './echo';

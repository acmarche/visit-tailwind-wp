import axios from './AxiosInstance.js';

/**
 * @param {string} language
 * @param {int} categoryId
 * @returns {Promise}
 */
export function fetchOffersByCategoryRequest(
    language,
    categoryId
) {
    const params = {};
    const url = `${language}/wp-json/pivot/category_offers/${categoryId}`;
    return axios.get(url, {
        params
    });
}

/**
 * @param {string} name
 * @returns {Promise}
 */
export function fetchOffersByNameOrCode(name) {
    const params = {};
    name = replaceAccents(name)
    const url = `wp-json/pivot/find-offers-by-name/${name.toLowerCase()}`;
    return axios.get(url, {
        params
    });
}

/**
 * @param {int} categoryId
 * @param {string} codeCgt
 * @returns {Promise}
 */
export function deleteOfferRequest(categoryId, codeCgt) {
    const url = 'wp-admin/admin-ajax.php';
    const formData = new FormData();
    formData.append('action', 'action_delete_offer');
    formData.append('categoryId', categoryId);
    formData.append('codeCgt', codeCgt);
    return axios.post(url, formData);
}

/**
 * @param {int} categoryId
 * @param {string} codeCgt
 * @returns {Promise}
 */
export function addOfferRequest(categoryId, codeCgt) {
    const url = 'wp-admin/admin-ajax.php';
    const formData = new FormData();
    formData.append('action', 'action_add_offer');
    formData.append('categoryId', categoryId);
    formData.append('codeCgt', codeCgt);
    return axios.post(url, formData);
}

function replaceAccents(name) {
    const translate = {
        "é": "e",
        "ê": "e",
        "è": "e",
        "à": "a",
        "ç": "c",
        "'": " ",
        "\"": " "
    };
    const translateRe = /[éèàê]/g;
    return name.replace(translateRe, (match) => {
        return translate[match];
    });
}
import axios from './AxiosInstance.js';

/**
 * @param {string} language
 * @param {int} categoryId
 * @param {int} flatWithChildren
 * @param {int} filterCount
 * @returns {Promise}
 */
export function fetchFiltresByCategoryRequest(
    language,
    categoryId,
    flatWithChildren = 0,
    filterCount = 1
) {
    const params = {};
    const url = `${language}/wp-json/pivot/category_filters/${categoryId}/${flatWithChildren}/${filterCount}`;
    return axios.get(url, {
        params
    });
}

/**
 * @param {string} name
 * @returns {Promise}
 */
export function fetchFiltresByName(name) {
    const params = {};
    name = replaceAccents(name)
    const url = `wp-json/pivot/filtres_name/${name.toLowerCase()}`;
    return axios.get(url, {
        params
    });
}

/**
 * @param {int} categoryId
 * @param {int} id
 * @returns {Promise}
 */
export function deleteFiltreRequest(categoryId, id) {
    const url = 'wp-admin/admin-ajax.php';
    const formData = new FormData();
    formData.append('action', 'action_delete_filtre');
    formData.append('categoryId', categoryId);
    formData.append('id', id);
    return axios.post(url, formData);
}

/**
 * @param {int} categoryId
 * @param {int} typeOffreId
 * @param {boolean} withChildren
 * @returns {Promise}
 */
export function addFiltreRequest(categoryId, typeOffreId, withChildren) {
    const url = 'wp-admin/admin-ajax.php';
    const formData = new FormData();
    formData.append('action', 'action_add_filtre');
    formData.append('categoryId', categoryId);
    formData.append('typeOffreId', typeOffreId);
    formData.append('withChildren', withChildren);
    return axios.post(url, formData);
}

function replaceAccents(name) {
    const translate = {
        é: "e",
        ê: "e",
        è: "e",
        à: "a",
        ç: "c",
    };
    const translateRe = /[éèàê]/g;
    return name.replace(translateRe, (match) => {
        return translate[match];
    });
}
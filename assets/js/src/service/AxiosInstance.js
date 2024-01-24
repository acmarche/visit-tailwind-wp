import axios from 'axios';

const instance = axios.create({
    baseURL: 'https://visit.marche.be/'
});

export default instance;

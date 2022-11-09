import axios from 'axios';

const instance = axios.create({
    baseURL: 'https://visit.marche.be/'
    //baseURL: 'https://visitmarche.be/'
});

export default instance;

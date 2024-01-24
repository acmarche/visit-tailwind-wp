import axios from 'axios';

const instance = axios.create({
    baseURL: 'https://visitmarche.be/'
});

export default instance;

import axios from 'axios';

const API_URL = '/api';

export default {
  async login(email, password) {
    const response = await axios.post(`${API_URL}/login_check`, {
      username: email,
      password: password
    });
    if (response.data.token) {
      localStorage.setItem('token', response.data.token);
      localStorage.setItem('user', JSON.stringify(response.data.data));
    }
    return response.data;
  },
  async register(name, email, password) {
    const response = await axios.post(`${API_URL}/register`, {
      name,
      email,
      password
    });
    return response.data;
  },
  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },
  getToken() {
    return localStorage.getItem('token');
  },
  getUser() {
    return JSON.parse(localStorage.getItem('user'));
  }
};

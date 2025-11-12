function isEmpty(object) {
    return Object
        .values(object)
        .every(val => typeof val === "undefined");
}

class Cookie {
    static COOKIE_TTL = 31536000;

    static COOKIE_PATH = '/';

    static SAME_SITE = `Strict`;

    static getCookie(key = '') {
        return document.cookie
            .split('; ')
            .find((row) => row.startsWith(key + '='))
            ?.split('=')[1];
    }

    static setCookie(
        key,
        value,
        maxAge = this.COOKIE_TTL,
        path = this.COOKIE_PATH
    ) {
        document.cookie = `${key}=${value};max-age=${maxAge};path=${path};`;
    }

    static deleteCookie(
        key,
        path = this.COOKIE_PATH
    ) {
        document.cookie = `${key}=;max-age=0;path=${path};`;
    }
}

class Server {
    static GET_PARAMS = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });

    static AUTH_HEADER = {
        'Authorization': `Bearer ${Server.getApiToken()}`,
    };

    static CONTENT_HEADER = {
        'Content-Type': 'application/json'
    };

    static AUTH_CONTENT_HEADERS = {
        'Authorization': `Bearer ${Server.getApiToken()}`,
        'Content-Type': 'application/json',
    };

    static AUTH_CONTENT_ACCEPT_HEADERS = {
        'Authorization': `Bearer ${Server.getApiToken()}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    };

    static getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    static getApiToken() {
        return decodeURI(Cookie.getCookie('api_token'));
    }

    static async getData(
        uri = '',
        headers = {},
        withErrorHandling = true,
        onlyData = true
    ) {
        if (isEmpty(headers)) {
            headers = Server.CONTENT_HEADER;
        }

        let url = window.location.origin + uri;
        let parameters = {
            method: 'GET',
            headers: headers,
            credentials: 'include',
        };

        let {response, responseJson} = await this.getResult(url, parameters);

        if (withErrorHandling) {
            let isOkResponse = this.checkResponse(await response, await responseJson);

            if (! Array.isArray(responseJson.data) && typeof responseJson.data !== 'object') {
                isOkResponse = false;

                Toast.showToastMessageWithTimeout(
                    'Ошибка',
                    'Неверный ответ сервера',
                    'error'
                );
            }

            if (! isOkResponse) {
                return false;
            }
        }

        if (onlyData) {
            return responseJson.data;
        }

        return responseJson;
    }

    static async getResult(url, parameters) {
        let response = await fetch(url, parameters);
        let isJson = response.headers.get('content-type')?.includes('application/json');
        let responseJson = isJson && await response.json();

        return {response, responseJson};
    }

    static async postData(
        uri = '',
        data = {},
        headers = {},
        withErrorHandling = true
    ) {
        if (isEmpty(headers)) {
            headers = Server.CONTENT_HEADER;
        }

        let url = window.location.origin + uri;
        let parameters = {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(data),
            credentials: 'include',
        };

        let {response, responseJson} = await this.getResult(url, parameters);

        if (withErrorHandling) {
            this.checkResponse(await response, await responseJson);
        }

        return responseJson;
    }

    static async putData(
        uri = '',
        data = {},
        headers = {},
        withErrorHandling = true
    ) {
        if (isEmpty(headers)) {
            headers = Server.AUTH_CONTENT_ACCEPT_HEADERS;
        }

        let url = window.location.origin + uri;
        let parameters = {
            method: 'PUT',
            headers: headers,
            body: JSON.stringify(data),
            credentials: 'include',
        };

        let {response, responseJson} = await this.getResult(url, parameters);

        if (withErrorHandling) {
            this.checkResponse(await response, await responseJson);
        }

        return responseJson;
    }

    static async deleteData(
        uri = '',
        headers = {},
        withErrorHandling = true
    ) {
        if (isEmpty(headers)) {
            headers = Server.CONTENT_HEADER;
        }

        let url = window.location.origin + uri;
        let parameters = {
            method: 'DELETE',
            headers: headers,
            body: JSON.stringify({
                '_token': Server.getCsrfToken(),
            }),
            credentials: 'include',
        };

        let {response, responseJson} = await this.getResult(url, parameters);

        if (withErrorHandling) {
            let isOkResponse = this.checkResponse(await response, await responseJson);

            if (isOkResponse) {
                Toast.showToastMessageWithTimeout(
                    'Успех',
                    'Данные удалены',
                    'success'
                );
            }
        }

        return response;
    }

    static checkResponse(response, responseJson = {}) {
        if (response.ok) {
            return true;
        }

        let error = (responseJson && responseJson.message) || response.status;

        Toast.showServerError(error);

        return false;
    }
}

class Url {
    static regexOfLocales = /\/(ru|en)\/*/;
    static arrayOfLocales = ['ru', 'en'];

    static getLanguage() {
        let resultOfRegex = Url.regexOfLocales.exec(window.location.href);

        if (resultOfRegex === null) {
            return 'en';
        }

        return resultOfRegex['1'];
    }

    static changeLanguage(language) {
        if (! Url.arrayOfLocales.includes(language)) {
            console.log('Undefined locale in changeLanguage function');

            return false;
        }

        Cookie.setCookie('locale', language);

        if (window.location.href.search(Url.regexOfLocales) === -1) {
            window.location.href = window.location.href.split('#')[0] + language;

            return true;
        }

        let resultOfRegex = Url.regexOfLocales.exec(window.location.href);

        window.location.href =
            window.location.href.substring(0, resultOfRegex.index)
            + '/'
            + language
            + window.location.href.substring(resultOfRegex.index + 3);

        return true;
    }
}

class Storage
{
    static set(
        key,
        value,
        timestampWhenExpired = (new DateTime()).addHours(4).getTimestamp()
    ) {
        let cachedObject = {
            value: value,
            expiration: timestampWhenExpired
        };

        localStorage.setItem(key, JSON.stringify(cachedObject));
    }

    static get(key) {
        if (! this.exists(key)){
            return '';
        }

        return this.getCachedObject(key).value;
    }

    static getCachedObject(key) {
        return JSON.parse(localStorage.getItem(key));
    }

    static exists(key) {
        let item = this.getCachedObject(key);

        if (Object.is(item, null)) {
            return false;
        }

        if (item.expiration < (new Date()).getTime()) {
            this.remove(key);
            return false;
        }

        return true;
    }

    static remove(key) {
        localStorage.removeItem(key);
    }

    static removeAll() {
        localStorage.clear();
    }
}

class DateTime
{
    dateTime;

    constructor(dateTime = '') {
        if (dateTime === '') {
            this.dateTime = new Date();
        } else {
            this.dateTime = new Date(dateTime);
        }
    }

    getDateObject() {
        return this.dateTime;
    }

    getTimestamp() {
        return this.dateTime.getTime();
    }

    addSeconds(seconds) {
        this.dateTime.setSeconds(
            this.dateTime.getSeconds() + seconds
        );

        return this;
    }

    addMinutes(minutes) {
        this.addSeconds(minutes * 60)

        return this;
    }

    addHours(hours) {
        this.addMinutes(hours * 60)

        return this;
    }

    addDays(days) {
        this.addHours(days * 24);

        return this;
    }

    addYears(years) {
        this.dateTime.setFullYear(
            this.dateTime.getFullYear() + years
        );

        return this;
    }

    toString() {
        return this.dateTime.toString();
    }
}

class Toast
{
    static showToastMessageWithTimeout(
        title = 'Validation Error',
        message = 'Please check the entered data',
        level = 'message',
        hideAfterSeconds = 5
    ) {
        this.showToastMessage(title, message, level);

        setTimeout(this.hideToastMessage, hideAfterSeconds * 1000);
    }

    static showToastMessage(
        title = 'Validation Error',
        message = 'Please check the entered data',
        level = 'message'
    ) {
        let toastDiv = document.querySelector('.toast');

        toastDiv.querySelector('.toast-level').classList.add('level-' + level);
        toastDiv.querySelector('.toast-title').innerText = title;
        toastDiv.querySelector('.toast-body').innerText = message;

        let bsToast = new bootstrap.Toast(toastDiv);

        bsToast.show();
    }

    static showMessageFromResponse(response, responseJson = null) {
        let ok = response.ok;
        let detail = responseJson?.detail || 'Response with status ' + response.status;

        if (responseJson?.hasOwnProperty('ok')) {
            ok = responseJson.ok;
        }

        if (! ok) {
            console.log(detail, responseJson?.data || []);
        }

        Toast.showToastMessageWithTimeout(ok ? 'Success: ' : 'Error: ', detail, ok ? 'success' : 'error');
    }

    static hideToastMessage() {
        let toastDiv = document.querySelector('.toast');
        let bsToast = new bootstrap.Toast(toastDiv);

        bsToast.hide();
    }

    static showServerError(error = '404') {
        this.showToastMessageWithTimeout(
            'Произошла ошибка',
            'Неверный запрос: ' + error,
            'error'
        );
    }
}

class DomElement
{
    static show(...elements) {
        elements.forEach(function (element) {
            element.classList.remove('animation-disappear');
            element.classList.add('animation-appear');
            element.style = 'display: block;';
        });
    }

    static hideWithTimeout(...elements) {
        elements.forEach(function (element) {
            element.classList.remove('animation-appear');
            element.classList.add('animation-disappear');

            setTimeout(DomElement.hide, 500, element);
        });
    }

    static hide(...elements) {
        elements.forEach(function (element) {
            element.style = 'display: none;';
        });
    }

    static hideVisibleWithTimeout(...elements) {
        elements.forEach(function (element) {
            element.classList.remove('animation-appear');
            element.classList.add('animation-disappear');

            setTimeout(DomElement.hideVisible, 500, element);
        });
    }

    static hideVisible(...elements) {
        elements.forEach(function (element) {
            if (window.getComputedStyle(element).display === 'none') {
                return;
            }

            element.style = 'display: block; visibility: hidden;';
        });
    }
}
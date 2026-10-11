// Minimal fetch client for the package's REST API (no axios/Vue needed).

function cookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
}

function csrfHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    const headers = { 'X-Requested-With': 'XMLHttpRequest' };

    if (meta && meta.content) headers['X-CSRF-TOKEN'] = meta.content;
    else if (cookie('XSRF-TOKEN')) headers['X-XSRF-TOKEN'] = cookie('XSRF-TOKEN');

    return headers;
}

function errorMessage(status, body) {
    if (body && body.errors) {
        const first = Object.values(body.errors)[0];
        if (first && first[0]) return first[0];
    }
    if (body && body.message) return body.message;
    if (status === 401) return 'You are not logged in.';
    if (status === 403) return 'You do not have permission to do that.';
    if (status === 419) return 'Your session expired. Reload the page and try again.';
    return `Request failed (${status}).`;
}

export function createApi(base) {
    const root = '/' + String(base || 'api/mail-customizer').replace(/^\/+|\/+$/g, '');

    async function request(method, path, body) {
        const isForm = typeof FormData !== 'undefined' && body instanceof FormData;
        const headers = { Accept: 'application/json', ...csrfHeaders() };
        if (body && !isForm) headers['Content-Type'] = 'application/json';

        let response;
        try {
            response = await fetch(root + path, {
                method,
                headers,
                credentials: 'same-origin',
                body: body ? (isForm ? body : JSON.stringify(body)) : undefined
            });
        } catch (e) {
            throw new Error('Could not reach the server. Check your connection.');
        }

        let data = null;
        try {
            data = await response.json();
        } catch (e) {
            /* empty or non-JSON body */
        }

        if (!response.ok) throw new Error(errorMessage(response.status, data));
        return data;
    }

    return {
        get: path => request('GET', path),
        post: (path, body) => request('POST', path, body),
        put: (path, body) => request('PUT', path, body),
        upload: file => {
            const form = new FormData();
            form.append('image', file);
            return request('POST', '/upload-image', form);
        }
    };
}

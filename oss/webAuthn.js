/*
 * This software contains the following modules.
 *  
 * [google/webauthndemo - https://github.com/google/webauthndemo/blob/master/src/main/webapp/js/webauthn.js]
 * Copyright 2017 Google Inc. All Rights Reserved.
 * Released under the Apache License (Version 2.0)
 * http://www.apache.org/licenses/
 */

function _fetch(url, obj) {
    let headers = new Headers({
        'Content-Type': 'application/x-www-form-urlencoded'
    });
    let body;
    if (typeof URLSearchParams === "function") {
        body = new URLSearchParams();
        for (let key in obj) {
            body.append(key, obj[key]);
        }
        // Set body to string value to handle an Edge case
        body = body.toString();
    } else {
        // Add parameters to body manually if browser doesn't support URLSearchParams
        body = "";
        for (let key in obj) {
            body += encodeURIComponent(key) + "=" + encodeURIComponent(obj[key]) + "&";
        }
    }

    return fetch(url, {
        method: 'POST',
        headers: headers,
        credentials: 'include',
        body: body
    }).then(response => {
        if (response.status === 200) {
            return response.json();
        } else {
            throw response.statusText;
        }
    });
}

function credentialListConversion(list) {
    return list.map(item => {
        const cred = {
            type: item.type,
            id: strToBin(item.id)
        };
        if (item.transports) {
            cred.transports = list.transports;
        }
        return cred;
    });
}

function makeCredential(loginId) {

    _fetch('/yamori4webAuthn/attestation/options.php', {
        loginId: loginId,
    }).then(options => {

        if (options && options.errorMsg) {
            alert(options.errorMsg);
            throw options.errorMsg;
        }

        const makeCredentialOptions = {};

        makeCredentialOptions.rp = options.rp;
        makeCredentialOptions.user = options.user;
        makeCredentialOptions.user.id = strToBin(options.user.id);
        makeCredentialOptions.challenge = strToBin(options.challenge);
        makeCredentialOptions.pubKeyCredParams = options.pubKeyCredParams;

        // Optional parameters
        if ('timeout' in options) {
            makeCredentialOptions.timeout = options.timeout;
        }
        if ('excludeCredentials' in options) {
            makeCredentialOptions.excludeCredentials = credentialListConversion(options.excludeCredentials);
        }
        if ('authenticatorSelection' in options) {
            makeCredentialOptions.authenticatorSelection = options.authenticatorSelection;
        }
        if ('attestation' in options) {
            makeCredentialOptions.attestation = options.attestation;
        }
        if ('extensions' in options) {
            makeCredentialOptions.extensions = options.extensions;
        }

        return navigator.credentials.create({
            "publicKey": makeCredentialOptions
        });

    }).then(attestation => {
        
        const publicKeyCredential = {};
        
        if ('id' in attestation) {
            publicKeyCredential.id = attestation.id;
        }
        if ('type' in attestation) {
            publicKeyCredential.type = attestation.type;
        }
        if ('rawId' in attestation) {
            publicKeyCredential.rawId = binToStr(attestation.rawId);
        }
        if (!attestation.response) {
            alert("Make Credential response lacking 'response' attribute");
        }
        
        const response = {};
        response.clientDataJSON = binToStr(attestation.response.clientDataJSON);
        response.attestationObject = binToStr(attestation.response.attestationObject);
        publicKeyCredential.response = response;
        
        return _fetch('/yamori4webAuthn/attestation/result.php', {
            loginId: loginId,
            register: JSON.stringify(publicKeyCredential)
        });
        
    }).then(parameters => {
        console.log(parameters);

        if (parameters && parameters.status === "ok") {
            alert("Registration success");
        } else if (parameters && parameters.errorMessage) {
            alert(parameters.errorMessage);
        } else {
            alert("Unexpected response received.");
        }

    }).catch(err => {
        alert(err.toString());
    });
}

function getAssertion(authnUsername) {

    _fetch('/yamori4webAuthn/assertion/options.php', {
        authnUsername: authnUsername
    }).then(parameters => {

        if (parameters && parameters.errorMsg) {
            alert(parameters.errorMsg);
            throw parameters.errorMsg;
        }

        const requestOptions = {};

        requestOptions.challenge = strToBin(parameters.challenge);
        if ('timeout' in parameters) {
            requestOptions.timeout = parameters.timeout;
        }
        if ('rpId' in parameters) {
            requestOptions.rpId = parameters.rpId;
        }
        if ('allowCredentials' in parameters) {
            requestOptions.allowCredentials = credentialListConversion(parameters.allowCredentials);
        }
        if ('userVerification' in parameters) {
            requestOptions.userVerification = parameters.userVerification;
        }
        console.log(requestOptions);

        return navigator.credentials.get({
            "publicKey": requestOptions
        });

    }).then(assertion => {
        console.log(assertion);

        const publicKeyCredential = {};

        if ('id' in assertion) {
            publicKeyCredential.id = assertion.id;
        }
        if ('type' in assertion) {
            publicKeyCredential.type = assertion.type;
        }
        if ('rawId' in assertion) {
            publicKeyCredential.rawId = binToStr(assertion.rawId);
        }
        if (!assertion.response) {
            throw "Get assertion response lacking 'response' attribute";
        }

        if (assertion.getClientExtensionResults) {
            if (assertion.getClientExtensionResults().uvm != null) {
              publicKeyCredential.uvm = serializeUvm(assertion.getClientExtensionResults().uvm);
            }
        }
      
        const _response = assertion.response;
      
        publicKeyCredential.response = {
          clientDataJSON:     binToStr(_response.clientDataJSON),
          authenticatorData:  binToStr(_response.authenticatorData),
          signature:          binToStr(_response.signature),
          userHandle:         binToStr(_response.userHandle)
        };

        return _fetch('/yamori4webAuthn/assertion/result.php', {
             loginId: loginId,
             authn: JSON.stringify(publicKeyCredential)
        });
        
    }).then(parameters => {
        console.log(parameters);

        if (parameters && parameters.status === "ok") {
            document.location.href = "/yamori4webAuthn/loginSuccess.php";
            return;

        } else if (parameters && parameters.errorMessage) {
            alert(parameters.errorMessage);
        } else {
            alert("Unexpected response received.");
        }

    }).catch(err => {
        console.log(err.toString());
    });
}

function strToBin(str) {
    // Replace non-url compatible chars with base64 standard chars
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    // Pad out with standard base64 required padding characters
    var padding = str.length % 4;
    if (padding) {
        str += new Array(5 - padding).join('=');
    }
    return Uint8Array.from(atob(str), c => c.charCodeAt(0));
}

function binToStr(bin) {
    return btoa(new Uint8Array(bin).reduce((s, byte) => s + String.fromCharCode(byte), ''))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/\=+$/, '');
}

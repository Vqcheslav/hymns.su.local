function copyText (text) {
    if (window.isSecureContext) {
        navigator.clipboard.writeText(text)
    } else {
        fallbackCopyTextToClipboard(text)
    }
}

function fallbackCopyTextToClipboard (text) {
    let textArea = document.createElement('textarea')
    textArea.value = text
    textArea.style.position = 'fixed'
    textArea.style.zIndex = '-1'
    textArea.style.opacity = '0'

    document.body.appendChild(textArea)
    textArea.select()
    textArea.focus()
    document.execCommand('copy')
    document.body.removeChild(textArea)
}

function clickButtonToCopy () {
    document.querySelectorAll('.copy-to-buffer').forEach(function (element) {
        element.addEventListener('click', async function () {
            let text = this.dataset.buffer
            console.log(text)
            await copyText(text)

            Toast.showToastMessageWithTimeout(
                'Успех',
                'Данные успешно скопированы: ' + text,
                'success',
            )
        })
    })
}

function setLoaderAndGetRemovingFunction (selectorOrElement) {
    let loader = '...'
    let element = typeof selectorOrElement === 'string'
        ? document.querySelector(selectorOrElement)
        : selectorOrElement
    let elementOldValue = element.value || element.innerText

    element.setAttribute('disabled', 'disabled')
    element.value = loader
    element.innerText = loader

    return function (newElementValue = null, removeDisableAttribute = true) {
        if (removeDisableAttribute) {
            element.removeAttribute('disabled')
        }

        element.value = newElementValue || elementOldValue
        element.innerText = newElementValue || elementOldValue
    }
}

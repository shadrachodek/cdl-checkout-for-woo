jQuery(function ($) {
    const copyButton = $('#copy-webhook-url');
    const webhookUrl = $('#webhook-url').text();

    copyButton.on('click', function (event) {
        event.preventDefault(); // Prevent the default action of the button

        if (navigator.clipboard) {
            navigator.clipboard.writeText(webhookUrl).then(function () {
                alert('copied to clipboard!');
            }, function (err) {
                console.error('Could not copy text: ', err);
            });
        } else {
            // Fallback method
            const tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(webhookUrl).select();
            try {
                document.execCommand('copy');
                alert('copied to clipboard!');
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
                alert('Failed to copy URL.');
            }
            tempInput.remove();
        }
    });
});

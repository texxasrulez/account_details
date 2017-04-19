        function select_all(obj) {
            var text_val=eval(obj);
            text_val.focus();
            text_val.select();
            if (!r.execCommand) return; // feature detection
            r = text_val.createTextRange();
            r.execCommand('copy');
        }

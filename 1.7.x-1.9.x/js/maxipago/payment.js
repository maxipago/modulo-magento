var maxipago = {
    processing: false,

    identifyCcNumber: function(ccNumber) {
        var creditCard = '';
        var visa = /^4\d{12,15}/;
        var master = /^5[1-5]{1}\d{14}/;
        var amex = /^(34|37)\d{13}/;
        var elo = /^(636368|438935|504175|451416|(6362|5067|4576|4011)\d{2})\d{10}/;
        var discover = /^(6011|622\d{1}|(64|65)\d{2})\d{12}/;
        var hipercard = /^(60\d{2}|3841)\d{9,15}/;
        var diners = /^((30(1|5))|(36|38)\d{1})\d{11}/;
        var jcb = /^(?:2131|1800|35\d{3})\d{11}/;
        var aura = /^50\d{14}/;

        if(visa.test(ccNumber)) {
            creditCard = 'VI';
        } else if(master.test(ccNumber)) {
            creditCard = 'MC';
        } else if(amex.test(ccNumber)) {
            creditCard = 'AM';
        } else if(discover.test(ccNumber)) {
            creditCard = 'DI';
        } else if(diners.test(ccNumber)) {
            creditCard = 'DC';
        } else if (elo.test(ccNumber)) {
            creditCard = 'EL';
        } else if(hipercard.test(ccNumber)) {
            creditCard = 'HC';
        } else if(jcb.test(ccNumber)) {
            creditCard = 'JC';
        } else if(aura.test(ccNumber)) {
            creditCard = 'AU';
        }

        return creditCard;
    },

    removeCard: function(url, customerId, confirmMessage) {
        var self = this;
        if (confim(confirmMessage)) {
            if (!self.processing) {
                var card = $j('select#savedCard option:selected').val();
                if (card != '0') {
                    self.processing = true;
                    $j.ajax({
                        url: url,
                        type: "post",
                        dataType: 'json',
                        data: {
                            'cId': card,
                            'custId': customerId
                        }
                    }).success(function (response) {
                        if (response.code == '200') {
                            $j('select#savedCard option:selected').remove();
                        }
                        self.processing = false;
                    }).error(function(){
                        self.processing = false;
                    });
                }
            }
        }
    }
};
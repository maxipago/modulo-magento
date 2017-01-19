function identificarCartaoCredito(ccNumber) {

    var eloRE = /^(636368|438935|504175|451416|(6362|5067|4576|4011)\d{2})\d{10}/;
    var visaRE = /^4\d{12,15}/;
    var masterRE = /^5[1-5]{1}\d{14}/;
    var amexRE = /^(34|37)\d{13}/;
    var discoveryRE = /^(6011|622\d{1}|(64|65)\d{2})\d{12}/;
    var hiperRE = /^(60\d{2}|3841)\d{9,15}/;
    var dinersRE = /^((30(1|5))|(36|38)\d{1})\d{11}/;

    try { document.getElementById('mpPaymentFlagVI').className = 'mpPaymentFlag mpPaymentFlagVI';} catch(err) { console.debug(err.message);}
    try { document.getElementById('mpPaymentFlagMC').className = 'mpPaymentFlag mpPaymentFlagMC';} catch(err) { console.debug(err.message);}
    try { document.getElementById('mpPaymentFlagDC').className = 'mpPaymentFlag mpPaymentFlagDC';} catch(err) { console.debug(err.message);}
    try { document.getElementById('mpPaymentFlagAM').className = 'mpPaymentFlag mpPaymentFlagAM';} catch(err) { console.debug(err.message);}
    try { document.getElementById('mpPaymentFlagELO').className = 'mpPaymentFlag mpPaymentFlagELO';} catch(err) { console.debug(err.message);}
    try { document.getElementById('mpPaymentFlagDI').className = 'mpPaymentFlag mpPaymentFlagDI';} catch(err) { console.debug(err.message);}
    try { document.getElementById('mpPaymentFlagHC').className = 'mpPaymentFlag mpPaymentFlagHC';} catch(err) { console.debug(err.message);}

    if(eloRE.test(ccNumber)){
        document.getElementById('mpPaymentMethod').value = 'ELO';
        document.getElementById('mpPaymentFlagELO').className = 'mpPaymentFlag mpPaymentFlagELO mpPaymentFlagSelected';
    }else if(visaRE.test(ccNumber)){
        document.getElementById('mpPaymentMethod').value = 'VI';
        document.getElementById('mpPaymentFlagVI').className = 'mpPaymentFlag mpPaymentFlagVI mpPaymentFlagSelected';
    }else if(masterRE.test(ccNumber)){
        document.getElementById('mpPaymentMethod').value = 'MC';
        document.getElementById('mpPaymentFlagMC').className = 'mpPaymentFlag mpPaymentFlagMC mpPaymentFlagSelected';
    }else if(amexRE.test(ccNumber)){
        document.getElementById('mpPaymentMethod').value = 'AM';
        document.getElementById('mpPaymentFlagAM').className = 'mpPaymentFlag mpPaymentFlagAM mpPaymentFlagSelected';
    }else if(discoveryRE.test(ccNumber)){
        document.getElementById('mpPaymentMethod').value = 'DI';
        document.getElementById('mpPaymentFlagDI').className = 'mpPaymentFlag mpPaymentFlagDI mpPaymentFlagSelected';
    }else if(hiperRE.test(ccNumber)){
        document.getElementById('mpPaymentMethod').value = 'HC';
        document.getElementById('mpPaymentFlagHC').className = 'mpPaymentFlagHC mpPaymentFlagSelected';
    }else if(dinersRE.test(ccNumber)){
        document.getElementById('mpPaymentMethod').value = 'DC';
        document.getElementById('mpPaymentFlagDC').className = 'mpPaymentFlag mpPaymentFlagDC mpPaymentFlagSelected';
    }
}

var ccSaveSelected = '0';
function selectCCSaved(obj, cc_type, maxipago_cc_token, cc_owner, cc_last4){
    var entity = obj.id.replace('ccEntity', '');
    
    document.getElementById('mpEntityId').value = maxipago_cc_token;
    document.getElementById('mpCCType').value = cc_type;
    document.getElementById('mpPaymentMethod').value = cc_type;
    document.getElementById('mpCcOwner').value = cc_owner;
    document.getElementById('mpCcLast4').value = cc_last4;

    obj.className = 'mpPaymentMethod mpPaymentCCSave mpPaymentCCSaveSelected';
    if (ccSaveSelected != '0' && entity != ccSaveSelected) {
        document.getElementById('ccEntity' + ccSaveSelected).className = 'mpPaymentMethod mpPaymentCCSave';
    }
    ccSaveSelected = entity;
}

function addNewCardMaxiPago(code) {
	clearCreditCard(code);
    document.getElementById('selectCreditCardMp').style.display = 'none';
    document.getElementById('newCreditCardMp').style.display = 'block';
    document.getElementById('displayCcInfo').style.display = 'block';
}

function selectCardMaxiPago(code) {
	clearCreditCard(code);
    document.getElementById('selectCreditCardMp').style.display = 'block';
    document.getElementById('newCreditCardMp').style.display = 'none';
    document.getElementById('displayCcInfo').style.display = 'none';
}

function clearCreditCard(code) {
	document.getElementById('mpPaymentFlagVI').className = 'mpPaymentFlag mpPaymentFlagVI'
	document.getElementById('mpPaymentFlagMC').className = 'mpPaymentFlag mpPaymentFlagMC'
	document.getElementById('mpPaymentFlagDC').className = 'mpPaymentFlag mpPaymentFlagDC'
	document.getElementById('mpPaymentFlagAM').className = 'mpPaymentFlag mpPaymentFlagAM'
	document.getElementById('mpPaymentFlagELO').className = 'mpPaymentFlag mpPaymentFlagELO'
	document.getElementById('mpPaymentFlagDI').className = 'mpPaymentFlag mpPaymentFlagDI'
	document.getElementById('mpPaymentFlagHC').className = 'mpPaymentFlag mpPaymentFlagHC'
	document.getElementById('mpEntityId').value = '';
    document.getElementById('mpCCType').value = '';
    document.getElementById('mpCcOwner').value = '';
    document.getElementById('mpCcLast4').value = '';
	document.getElementById('mpPaymentMethod').value = '';
	document.getElementById(code + '_cc_owner').value = '';
	document.getElementById(code + '_cc_number').value = '';
	document.getElementById(code + '_expiration').value = '0';
	document.getElementById(code + '_expiration_yr').value = '0';
	document.getElementById(code + '_cc_cid').value = '';
	if (ccSaveSelected != '0') {
		document.getElementById('ccEntity' + ccSaveSelected).className = 'mpPaymentMethod mpPaymentCCSave';
		ccSaveSelected = '0';
	}
}
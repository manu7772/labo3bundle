// Fonctions basiques et ne nécessitant pas JQuery

// Teste si le mail est conforme au format mail (renvoie true ou false)
function isMail(mail) {
	var exp=new RegExp("^[a-zA-Z0-9\-_]+[a-zA-Z0-9\.\-_]*@[a-zA-Z0-9\-_]+\.[a-zA-Z\.\-_]{1,}[a-zA-Z\-_]+");
	// if(exp.test(mail)) alert("Mail valide !"); else alert("Mail non valide !");
	return exp.test(mail);
}

// Retourne une phrase équivalente à tx en changeant tous les caractères de manière aléatoire
function facticeText(tx) {
	this.t = "";
	this.J = 0;
	this.l = tx.length;
	for(this.i=0; i<l; i++) {
		n1 = Math.round(Math.random() * 100);
		if(n1 < 33) J = 65 + Math.floor((Math.random() * 26));
		else if(n1 > 66) J = 48 + Math.floor((Math.random() * 10));
		else J = 97 + Math.floor((Math.random() * 10));
		t = t + String.fromCharCode(J);
	}
	return t;
}

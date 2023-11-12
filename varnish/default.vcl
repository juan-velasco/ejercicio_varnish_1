vcl 4.0;

backend default {
   .host = "backend:80";
}

sub vcl_init {
}

# se ejecuta al principio del ciclo de vida de una solicitud
sub vcl_recv {
	if (req.method != "GET" && req.method != "HEAD") {
	  	return (pass); # no se almacenará en caché, se pasa directamente al servidor
    }

	if (req.http.X-Requested-With == "XMLHttpRequest") {
    		return(pass); # no se almacenará en caché, se pasa directamente al servidor
    }

	if (req.http.Authorization || req.method == "POST") {
	  	return (pass); # no se almacenará en caché, se pasa directamente al servidor
    }
	
	if (req.url ~ "(es/admin|es/login)") {
	    	return(pass); # no se almacenará en caché, se pasa directamente al servidor
    }

    if (req.url ~ "^/es/blog") {
        return (hash); # se hace uso de caché
    }
}

# Se ejecuta después de recibir la respuesta del servidor backend. Permite manipular la respuesta antes de que se almacene en el caché de Varnish o se entregue al cliente.
sub vcl_backend_response {
    # Habilitar el caché solo para respuestas con un código 200 OK
    if (beresp.status == 200) {
        set beresp.ttl = 24h; # Tiempo de vida del caché en segundos
        set beresp.uncacheable = false;
    } else {
        set beresp.uncacheable = true;
        set beresp.ttl = 120s; # Tiempo de vida del caché para otros códigos de respuesta
    }
    return (deliver);
}

sub vcl_deliver {
    if (obj.hits > 0) {
        # La solicitud ha hecho cache hit
        set resp.http.X-Cache = "HIT";
    } else {
        # La solicitud no ha hecho cache hit
        set resp.http.X-Cache = "MISS";
    }

    # Agregar encabezados adicionales si es necesario
    # Por ejemplo, puedes incluir información sobre la fecha de la última modificación, etc.
    
    # Entregar la respuesta al cliente
    return (deliver);
}
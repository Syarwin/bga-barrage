const handles = {};

export const attach = function(node, handle, event = "click", group) {
    const handleWrapper = e => {
        handle(e, () => {
            node.removeEventListener(event, handleWrapper);
        })
    }

    node.addEventListener(event, handleWrapper);

    if (group !== undefined) {
        if (!handles[group]) {
            handles[group] = [];
        }
    
        handles[group].push({node, event, handle: handleWrapper});
    }
}

export const autodetach = (handle) => {
    return (e, detach) => {
        detach();
        handle(e);
    }
}

export const attachQuery = function(query, handle, event = "click", group) {
    document.querySelectorAll(query).forEach(node => {
        attach(node, handle, event, group);
    });
}
export const detachAll = function(){
    for (var group in handles) {
        if (handles.hasOwnProperty(group)) {
            detachGroup(group);
        }
    }
}

export const detachGroup = function(group) {
    if (!handles[group]) {
        return;
    }

    handles[group].forEach(function(listener) {
        listener.node.removeEventListener(listener.event, listener.handle);
    });
    handles[group] = [];
};
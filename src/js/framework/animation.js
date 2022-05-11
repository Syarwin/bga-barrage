export const slideFrom = (node, fromNode, animatedClass = "animated") => {
    return new Promise((resolve, reject) => {
        node.classList.add("notransition");

        const originalTop = node.style.top;
        const originalLeft = node.style.left;

        moveOnTopAnElement(node, fromNode, {}, "px");

        setTimeout(() => {
            node.classList.add(animatedClass);
            node.classList.remove("notransition");
            node.style.top = originalTop;
            node.style.left = originalLeft;

            const onTransitionEnd = () => {
                node.classList.remove(animatedClass);
                node.removeEventListener("transitionend", onTransitionEnd);
                resolve(node);
            }

            node.addEventListener("transitionend", onTransitionEnd);
        }, 0);
    });
}

export const moveBack = (node, withoutTransition = false) => {
    if (withoutTransition) {
        node.classList.add("notransition");
    }
    node.style.top = null;
    node.style.left = null;

    if (withoutTransition) {
        setTimeout(() => {
            node.classList.remove("notransition");
        }, 0);
    }
}

export const moveOnTopAnElement = (node, target, offset = {}, units = "%") => {
    offset = {
        top: 0,
        left: 0,
        ...offset
    };
    const rect = node.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();
    const topDiff = targetRect.top - rect.top;
    const leftDiff = targetRect.left - rect.left;

    const currentTop = getComputedStyle(node).top.replace("px", "");
    const currentLeft = getComputedStyle(node).left.replace("px", "");

    if (currentTop === "auto" || currentLeft === "auto") {
        throw new Error("Node must have a position");
    }

    const newTopPx = +currentTop + topDiff + targetRect.height / 2 - rect.height / 2;
    const newLeftPx = +currentLeft + leftDiff + targetRect.width / 2 - rect.width / 2;

    if (units === "px") {
        node.style.top = `${newTopPx + offset.top}${units}`;
        node.style.left = `${newLeftPx + offset.left}${units}`;
    }

    if (units === "%") {
        const parent = node.parentNode;
        const parentHeight = getComputedStyle(parent).height.replace("px", "");
        const parentWidth = getComputedStyle(parent).width.replace("px", "");

        node.style.top = `${newTopPx / parentHeight * 100 + offset.top}${units}`;
        node.style.left = `${newLeftPx  / parentWidth * 100 + offset.left}${units}`;
    }
}

export const cloneAndSlideTo = (element, target, offset = {}, placementCallback = null, destroyOriginal = true, animatedClass = "animated") => {
    return new Promise((resolve, reject) => {
        const movingObject = element.cloneNode(true);
        if (!placementCallback) {
            placementCallback = () => {
                target.appendChild(movingObject);
            }
        }

        placementCallback(movingObject);

        movingObject.classList.add("notransition");
        movingObject.classList.add(animatedClass);
        moveOnTopAnElement(movingObject, element, {}, "px");

        if (destroyOriginal) {
            element.remove();
        }

        setTimeout(() => {
            movingObject.classList.remove("notransition");
            moveOnTopAnElement(movingObject, target, offset, "px");

            const onTransitionEnd = () => {
                movingObject.classList.remove(animatedClass);
                resolve(movingObject);
                movingObject.removeEventListener("transitionend", onTransitionEnd);
            }

            movingObject.addEventListener('transitionend', onTransitionEnd);
        }, 0)
    
    });
}

export const cloneSlideToAndDestroy = (element, target, offset = {}, placementCallback = null, destroyOriginal = true, animatedClass = "animated") => {
    return cloneAndSlideTo(element, target, offset, placementCallback, destroyOriginal, animatedClass).then(movingObject => {
            movingObject.remove();
    });
}
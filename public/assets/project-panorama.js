(() => {
    document.querySelectorAll('[data-panorama]').forEach(root => {
        const start = root.querySelector('[data-panorama-start]');
        const view = root.querySelector('[data-panorama-view]');
        const fallback = root.querySelector('[data-panorama-fallback]');
        const canvas = root.querySelector('canvas');
        const status = root.querySelector('[data-panorama-status]');
        let gl, program, yaw = 0, pitch = 0, fov = 75, ready = false, pointer = null;
        start.hidden = false;
        const draw = () => {
            if (!ready || view.hidden) return;
            const width = Math.min(1440, Math.max(320, Math.round(canvas.clientWidth * Math.min(devicePixelRatio || 1, 1.5))));
            canvas.width = width;
            canvas.height = Math.round(width * 9 / 16);
            gl.viewport(0, 0, canvas.width, canvas.height);
            gl.uniform3f(gl.getUniformLocation(program, 'camera'), yaw, pitch, Math.tan(fov * Math.PI / 360));
            gl.uniform1f(gl.getUniformLocation(program, 'aspect'), canvas.width / canvas.height);
            gl.drawArrays(gl.TRIANGLES, 0, 6);
        };
        const fail = () => {
            ready = false;
            view.hidden = true;
            fallback.hidden = false;
            start.hidden = true;
            status.textContent = 'Interactive viewing is unavailable in this browser. You can still open the panorama image above.';
        };
        const initialize = async () => {
            gl = canvas.getContext('webgl', {alpha:false, antialias:false});
            if (!gl) throw new Error('WebGL unavailable');
            const shader = (type, source) => {
                const result = gl.createShader(type);
                gl.shaderSource(result, source);
                gl.compileShader(result);
                if (!gl.getShaderParameter(result, gl.COMPILE_STATUS)) throw new Error('Shader unavailable');
                return result;
            };
            program = gl.createProgram();
            gl.attachShader(program, shader(gl.VERTEX_SHADER, 'attribute vec2 position; varying vec2 point; void main(){point=position;gl_Position=vec4(position,0.,1.);}'));
            gl.attachShader(program, shader(gl.FRAGMENT_SHADER, `
                precision mediump float;
                varying vec2 point; uniform sampler2D panorama; uniform vec3 camera; uniform float aspect;
                void main(){
                    vec3 ray=normalize(vec3(point.x*aspect*camera.z,point.y*camera.z,1.));
                    float cp=cos(camera.y),sp=sin(camera.y),cy=cos(camera.x),sy=sin(camera.x);
                    ray=vec3(ray.x,ray.y*cp+ray.z*sp,ray.z*cp-ray.y*sp);
                    ray=vec3(ray.x*cy+ray.z*sy,ray.y,ray.z*cy-ray.x*sy);
                    vec2 uv=vec2(fract(atan(ray.x,ray.z)/6.2831853+0.5),0.5-asin(clamp(ray.y,-1.,1.))/3.1415927);
                    gl_FragColor=texture2D(panorama,uv);
                }`));
            gl.linkProgram(program);
            if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error('Viewer unavailable');
            gl.useProgram(program);
            const buffer = gl.createBuffer();
            gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
            gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,1,-1,-1,1,-1,1,1,-1,1,1]), gl.STATIC_DRAW);
            const position = gl.getAttribLocation(program, 'position');
            gl.enableVertexAttribArray(position);
            gl.vertexAttribPointer(position, 2, gl.FLOAT, false, 0, 0);
            const image = new Image();
            image.src = fallback.querySelector('a').href;
            await image.decode();
            const texture = gl.createTexture();
            gl.bindTexture(gl.TEXTURE_2D, texture);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
            gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
            gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, image);
            if (gl.getError() !== gl.NO_ERROR) throw new Error('Image unavailable');
            ready = true;
        };
        start.addEventListener('click', async () => {
            start.disabled = true;
            status.textContent = 'Loading interactive view…';
            try {
                if (!ready) await initialize();
                fallback.hidden = true;
                view.hidden = false;
                start.hidden = true;
                status.textContent = '';
                draw();
                canvas.focus();
            } catch { fail(); }
            finally { start.disabled = false; }
        });
        const move = action => {
            if (action === 'left') yaw -= .15;
            if (action === 'right') yaw += .15;
            if (action === 'up') pitch += .12;
            if (action === 'down') pitch -= .12;
            if (action === 'in') fov -= 10;
            if (action === 'out') fov += 10;
            if (action === 'reset') { yaw = 0; pitch = 0; fov = 75; }
            pitch = Math.max(-1.4, Math.min(1.4, pitch));
            fov = Math.max(35, Math.min(100, fov));
            yaw %= Math.PI * 2;
            draw();
        };
        root.querySelectorAll('[data-panorama-action]').forEach(button => button.addEventListener('click', () => move(button.dataset.panoramaAction)));
        canvas.addEventListener('keydown', event => {
            const action = {ArrowLeft:'left',ArrowRight:'right',ArrowUp:'up',ArrowDown:'down','+':'in','=':'in','-':'out',Home:'reset'}[event.key];
            if (action) { event.preventDefault(); move(action); }
        });
        canvas.addEventListener('pointerdown', event => {
            if (event.button !== 0) return;
            pointer = {id:event.pointerId,x:event.clientX,y:event.clientY};
            canvas.setPointerCapture(event.pointerId);
        });
        canvas.addEventListener('pointermove', event => {
            if (!pointer || pointer.id !== event.pointerId) return;
            yaw -= (event.clientX - pointer.x) * .005;
            pitch += (event.clientY - pointer.y) * .005;
            pointer.x = event.clientX; pointer.y = event.clientY;
            move('drag');
        });
        ['pointerup','pointercancel','lostpointercapture'].forEach(type => canvas.addEventListener(type, () => { pointer = null; }));
        root.querySelector('[data-panorama-close]').addEventListener('click', () => {
            view.hidden = true; fallback.hidden = false; start.hidden = false; start.focus();
        });
        canvas.addEventListener('webglcontextlost', event => { event.preventDefault(); fail(); });
        window.addEventListener('resize', draw);
    });
})();

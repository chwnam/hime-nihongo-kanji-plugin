import {createRoot} from 'react-dom/client'

// wp_localize_script 함수를 통해 전역변수로 전달되는 값의 타입 지정 
declare global {
    let helloScript: {
        name: string
    }
}

createRoot(document.getElementById('hello-script-root')!)!.render(
        <p>{helloScript.name}, 안녕하신지요?</p>
)

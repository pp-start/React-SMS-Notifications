import { Outlet } from 'react-router-dom';

export default function Common() {
    return (
    <>
        <div id="main-container">
            <Outlet />
        </div>
    </>
    )
}
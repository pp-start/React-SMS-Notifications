import React, { Suspense } from 'react';
import { useEffect, useContext } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { UserContext } from './components/UserContext';
import { Common, Error, Main, Admin } from './index';
import CacheBuster from 'react-cache-buster';
import Loading from './components/Loading';
import packageInfo from '../package.json';

// Importing component based on defined login method

const loginMethod = process.env.REACT_APP_LOGIN_METHOD;

const Login = React.lazy(() => 
  loginMethod === 'mail'
    ? import('./components/LoginMail') 
    : import('./components/LoginPhone')
);

export default function App() {

    useEffect(() => {

        document.title = 'React SMS & Notifications';

    }, []);

    const {user} = useContext(UserContext);

    const isProduction = process.env.NODE_ENV === 'production';

    return (
        <CacheBuster
            currentVersion={packageInfo.version}
            isEnabled={isProduction} //If false, the library is disabled.
            isVerboseMode={false} //If true, the library writes verbose logs to console.
            loadingComponent={<Loading />} //If not pass, nothing appears at the time of new version check.
            metaFileDirectory={'.'} //If public assets are hosted somewhere other than root on your server.
        >
            <>
                {user && user.role === 'none' && 
                    <BrowserRouter>
                        <Suspense fallback={<Loading />}>
                            <Routes>
                                <Route path='/' element={<Common />}>
                                    <Route index element={<Login />} />
                                    <Route path='*' element={<Navigate to='/' />} />
                                </Route>
                            </Routes>
                        </Suspense>
                    </BrowserRouter>
                }
                {user && user.role === 'user' && 
                    <BrowserRouter>
                        <Routes>
                            <Route path='/' element={<Common/>}>
                                <Route index element={<Main/>} />
                                <Route path='*' element={<Error/>} />
                            </Route>
                        </Routes>
                    </BrowserRouter>
                }
                {user && user.role === 'admin' && 
                    <BrowserRouter>
                        <Routes>
                            <Route path='/' element={<Common/>}>
                                <Route index element={<Main/>}/>
                                <Route path='/admin' element={<Admin/>}/>
                                <Route path='*' element={<Error/>}/>
                            </Route>
                        </Routes>
                    </BrowserRouter>
                }
            </>
        </CacheBuster>
    );
}
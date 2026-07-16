import { lazy, Suspense, useEffect } from "react";
import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import { useUserContext } from "./components/Context";
import { Common, Error, Main, Offline, Admin } from "./main";
import CacheBuster from "react-cache-buster";
import Loading from "./components/Loading";
import packageInfo from "../package.json";

export default function App() {
  const { user, isOnline } = useUserContext();
  const loginMethod = import.meta.env.VITE_APP_LOGIN_METHOD;
  const Login =
    loginMethod === "mail"
      ? lazy(() => import("./components/LoginMail"))
      : lazy(() => import("./components/LoginPhone"));

  useEffect(() => {
    document.title = "Auth Application";
  }, []);

  const isProduction: boolean = import.meta.env.MODE === "production";

  return (
    <CacheBuster
      currentVersion={packageInfo.version}
      isEnabled={isProduction}
      isVerboseMode={false}
      loadingComponent={<Loading />}
      metaFileDirectory={"."}
      onCacheClear={() => window.location.reload()}
    >
      <>
        {user /*isOnline &&*/ && (
          <>
            {user.role === "none" && (
              <BrowserRouter>
                <Routes>
                  <Route path="/" element={<Common />}>
                    <Route
                      index
                      element={
                        <Suspense fallback={<Loading />}>
                          <Login />
                        </Suspense>
                      }
                    />
                    <Route path="*" element={<Navigate to="/" />} />
                  </Route>
                </Routes>
              </BrowserRouter>
            )}
            {user.role === "user" && (
              <BrowserRouter>
                <Routes>
                  <Route path="/" element={<Common />}>
                    <Route index element={<Main />} />
                    <Route path="*" element={<Error />} />
                  </Route>
                </Routes>
              </BrowserRouter>
            )}
            {user.role === "admin" && (
              <BrowserRouter>
                <Routes>
                  <Route path="/" element={<Common />}>
                    <Route index element={<Admin />} />
                    <Route path="*" element={<Error />} />
                  </Route>
                </Routes>
              </BrowserRouter>
            )}
          </>
        )}
        {!isOnline && (
          <BrowserRouter>
            <Routes>
              <Route path="/" element={<Common />}>
                <Route index element={<Offline />} />
                <Route path="*" element={<Navigate to="/" />} />
              </Route>
            </Routes>
          </BrowserRouter>
        )}
      </>
    </CacheBuster>
  );
}

import { Outlet } from "react-router-dom";

export default function Common(): React.JSX.Element {
  return (
    <>
      <div id="main-container">
        <Outlet />
      </div>
    </>
  );
}

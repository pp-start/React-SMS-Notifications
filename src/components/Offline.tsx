import ErrorIcon from "../images/error2.png";

export default function Offline(): React.JSX.Element {
  return (
    <div id="error-outer-container">
      <div id="error-inner-container">
        <p className="text-error">Lost connection with internet.</p>
        <img src={ErrorIcon} alt="error" className="error-image" />
        <p className="text-error">
          App will resume automatically<br></br>when connection will be
          restored.
        </p>
      </div>
    </div>
  );
}

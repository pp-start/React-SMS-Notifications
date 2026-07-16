import { Link } from "react-router-dom";
import ErrorIcon from "../images/error.png";

export default function Error(): React.JSX.Element {
  return (
    <div id="error-outer-container">
      <Link to="/">
        <div id="error-inner-container">
          <p className="text-error">
            It appears that <br></br>nothing is here.
          </p>
          <img src={ErrorIcon} alt="error" className="error-image" />
          <p className="text-error">Click to go back.</p>
        </div>
      </Link>
    </div>
  );
}

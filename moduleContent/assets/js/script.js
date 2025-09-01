document.getElementById('quizForm').addEventListener('submit', function
(e){
    e.preventDefault();

    let score =0;
    const totalQuestions = correctAns.length;

    correctAns.forEach((answer, ind) => {
        const selected = document.querySelector(`input[name="question${ind}"]:checked`)

        if(selected && parseInt(selected.value) === answer){
            score++;
        }
    });
    
    if(score == totalQuestions){
        document.getElementById('result').innerHTML= `<h2 id="result">You scored: ${score} / ${totalQuestions} Module complete</h2>`;
        //add certificat generation here
    } else {
        document.getElementById('result').innerHTML= `<h2 id="result">You scored: ${score} / ${totalQuestions} Please try again</h2>`;
    }
})
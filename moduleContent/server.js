//server 

const express = require('express')
const path = require('path')
const questionList = require('./data')

const app = express()

app.use(express.static(path.join(__dirname, 'assets')))
app.set('view engine', 'ejs')

//send info from data to index referenced as 'question' can repeat for all pages or find cleaner way to implement when more modules are done
app.get('/', (req, res)=>{
    res.render('index', {questions: questionList.Questions})

})

app.listen(3000, ()=>{
    console.log("server is open on Port 3000")
})